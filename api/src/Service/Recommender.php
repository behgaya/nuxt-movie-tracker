<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\WatchedMovie;
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
        $watched = $this->movies->listFor($user);
        $watchedIds = array_flip(array_map(fn (WatchedMovie $m) => $m->getTmdbId(), $watched));

        $scores = [];
        $found = [];
        $because = [];
        foreach ($this->seeds($watched) as $seed) {
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
                $scores[$id] = ($scores[$id] ?? 0) + $weight * (1 - $rank / 40);
                $found[$id] ??= $movie;
                $because[$id][] = $seed->getTitle();
            }
        }

        foreach ($scores as $id => $score) {
            $scores[$id] = $score * $this->quality($found[$id]['vote_average'] ?? 0);
        }
        arsort($scores);

        return array_map(fn (int $id) => [
            'id' => $id,
            'title' => $found[$id]['title'] ?? '',
            'poster_path' => $found[$id]['poster_path'] ?? null,
            'release_date' => $found[$id]['release_date'] ?? '',
            'overview' => $found[$id]['overview'] ?? '',
            'vote_average' => $found[$id]['vote_average'] ?? 0,
            'vote_count' => $found[$id]['vote_count'] ?? 0,
            'because' => $because[$id],
        ], \array_slice(array_keys($scores), 0, self::LIMIT));
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
