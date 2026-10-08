<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\WatchedMovie;
use App\Enum\MovieStatus;
use App\Repository\WatchedMovieRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Recommends movies from a user's watched list: asks TMDB what people who liked each
 * "seed" movie also liked, then ranks the results. A movie recommended by several seeds,
 * or by higher-rated seeds, ranks higher.
 */
final class Recommender
{
    /** How many watched movies to ask TMDB about (each one is a TMDB call, cached for a day) */
    public const MAX_SEEDS = 10;

    public const LIMIT = 20;

    /** Below this many TMDB votes a score means little, so the movie is left out */
    public const MIN_VOTES = 50;

    /**
     * Each recommendation a seed already placed in the list makes its next ones count this much less
     * (0.7, then 0.49, ...). Without it, one 5-star seed fills the whole list, because even its worst
     * match outscores the best match of an unrated seed.
     */
    public const REPEAT_DECAY = 0.7;

    public function __construct(
        private readonly WatchedMovieRepository $movies,
        private readonly TmdbClient $tmdb,
    ) {
    }

    /**
     * @return list<array> TMDB movies, best match first, each with "because": the titles of the seeds that led to it
     */
    public function for(User $user): array
    {
        $watched = $this->movies->listFor($user, MovieStatus::Watched);
        $watchedIds = array_flip(array_map(fn (WatchedMovie $m) => $m->getTmdbId(), $watched));

        $seeds = $this->seeds($watched);
        $points = []; // movie id => seed index => what that seed adds to the movie's score
        $found = [];
        foreach ($seeds as $s => $seed) {
            try {
                $results = $this->tmdb->recommendations($seed->getTmdbId())['results'] ?? [];
            } catch (NotFoundHttpException) {
                continue; // TMDB no longer knows this movie: skip it rather than fail the page
            }

            $weight = $this->weight($seed);
            foreach ($results as $rank => $movie) {
                $id = $movie['id'];
                if (isset($watchedIds[$id]) || ($movie['vote_count'] ?? 0) < self::MIN_VOTES) {
                    continue;
                }
                // TMDB lists its best matches first, so earlier results count slightly more
                $points[$id][$s] = $weight * (1 - $rank / 40);
                $found[$id] ??= $movie;
            }
        }

        return array_map(fn (int $id) => [
            'id' => $id,
            'title' => $found[$id]['title'] ?? '',
            'poster_path' => $found[$id]['poster_path'] ?? null,
            'release_date' => $found[$id]['release_date'] ?? '',
            'overview' => $found[$id]['overview'] ?? '',
            'vote_average' => $found[$id]['vote_average'] ?? 0,
            'vote_count' => $found[$id]['vote_count'] ?? 0,
            'because' => array_map(fn (int $s) => $seeds[$s]->getTitle(), array_keys($points[$id])),
        ], $this->pick($points, $found));
    }

    /**
     * Picks the best movie, one at a time. A movie's score is what each of its seeds adds, times
     * REPEAT_DECAY for every movie that seed already got into the list, then nudged by quality().
     * So a loved seed still leads, but the other seeds' best matches get their turn too.
     *
     * @param array<int, array<int, float>> $points movie id => seed index => points
     * @param array<int, array>             $found  movie id => TMDB movie
     *
     * @return list<int> movie ids, best first
     */
    private function pick(array $points, array $found): array
    {
        $picked = []; // seed index => how many picked movies it recommended
        $list = [];

        while ($points && \count($list) < self::LIMIT) {
            $best = null;
            $bestScore = -1;
            foreach ($points as $id => $bySeed) {
                $score = 0;
                foreach ($bySeed as $s => $p) {
                    $score += $p * self::REPEAT_DECAY ** ($picked[$s] ?? 0);
                }
                $score *= $this->quality($found[$id]['vote_average'] ?? 0);
                if ($score > $bestScore) {
                    $best = $id;
                    $bestScore = $score;
                }
            }

            $list[] = $best;
            foreach (array_keys($points[$best]) as $s) {
                $picked[$s] = ($picked[$s] ?? 0) + 1;
            }
            unset($points[$best]);
        }

        return $list;
    }

    /**
     * Highest-rated movies first, then the most recently watched. Movies rated 1 or 2 are left out:
     * the user didn't like them, so "more like this" would be the wrong advice.
     *
     * @param list<WatchedMovie> $watched newest first
     *
     * @return list<WatchedMovie>
     */
    private function seeds(array $watched): array
    {
        $liked = array_filter($watched, fn (WatchedMovie $m) => ($m->getRating() ?? 3) >= 3);
        // usort is stable, so movies with the same rating stay newest first
        usort($liked, fn (WatchedMovie $a, WatchedMovie $b) => ($b->getRating() ?? 3) <=> ($a->getRating() ?? 3));

        return \array_slice($liked, 0, self::MAX_SEEDS);
    }

    /**
     * A gentle nudge from the TMDB score (0–10): 0.8 for a 0, 0.96 for an 8, 1.0 for a 10.
     * It reorders movies your list likes about equally, but never beats a movie that more
     * of your seeds recommend; otherwise every list would fill up with the same classics.
     */
    private function quality(float $voteAverage): float
    {
        return 0.8 + $voteAverage / 50;
    }

    /** 5 stars counts three times as much as an unrated or 3-star movie */
    private function weight(WatchedMovie $seed): int
    {
        return match ($seed->getRating()) {
            5 => 3,
            4 => 2,
            default => 1,
        };
    }
}
