<?php

namespace App\Service;

use App\Dto\BulkInput;
use App\Dto\MovieInput;
use App\Dto\ReviewInput;
use App\Entity\User;
use App\Entity\WatchedMovie;
use App\Enum\MovieStatus;
use App\Repository\WatchedMovieRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * A user's watched and want-to-watch lists. Every method returns the whole updated list
 * it changed, as the frontend expects.
 */
final class WatchList
{
    public function __construct(
        private readonly WatchedMovieRepository $movies,
        private readonly EntityManagerInterface $em,
    ) {
    }

    /** @return list<array> newest first */
    public function list(User $user, MovieStatus $status = MovieStatus::Watched): array
    {
        return array_map(fn (WatchedMovie $m) => $m->toArray(), $this->movies->listFor($user, $status));
    }

    /** Marks a movie as watched. A wanted movie moves from the want list to the watched list. */
    public function add(User $user, MovieInput $movie): array
    {
        $existing = $this->movies->findOneFor($user, $movie->id);

        if (!$existing) {
            $this->insert(new WatchedMovie($user, $movie, MovieStatus::Watched, new \DateTimeImmutable()));
        } elseif (MovieStatus::Want === $existing->getStatus()) {
            $existing->markWatched(new \DateTimeImmutable());
            $this->em->flush();
        }

        return $this->list($user);
    }

    /** Adds a movie to the want list. Does nothing if it is in either list already. */
    public function want(User $user, MovieInput $movie): array
    {
        if (!$this->movies->findOneFor($user, $movie->id)) {
            $this->insert(new WatchedMovie($user, $movie, MovieStatus::Want, new \DateTimeImmutable()));
        }

        return $this->list($user, MovieStatus::Want);
    }

    public function remove(User $user, int $tmdbId, MovieStatus $status = MovieStatus::Watched): array
    {
        $this->movies->removeFor($user, $status, [$tmdbId]);

        return $this->list($user, $status);
    }

    public function review(User $user, int $tmdbId, ReviewInput $input): array
    {
        // Only watched movies can be reviewed
        $movie = $this->movies->findOneFor($user, $tmdbId, MovieStatus::Watched)
            ?? throw new NotFoundHttpException('Movie is not in the watched list');

        $movie->setReview($input->rating, $input->review);
        $this->em->flush();

        return $this->list($user);
    }

    /**
     * Adds and removes many watched movies in one transaction: either all changes apply or none do.
     * Like add(), it moves wanted movies to the watched list.
     */
    public function bulk(User $user, BulkInput $input): array
    {
        $this->em->wrapInTransaction(function () use ($user, $input) {
            $this->movies->removeFor($user, MovieStatus::Watched, $input->remove);

            $existing = [];
            foreach ([...$this->movies->listFor($user, MovieStatus::Watched), ...$this->movies->listFor($user, MovieStatus::Want)] as $m) {
                $existing[$m->getTmdbId()] = $m;
            }

            $now = new \DateTimeImmutable();
            foreach ($input->add as $movie) {
                if (!isset($existing[$movie->id])) {
                    $this->em->persist($existing[$movie->id] = new WatchedMovie($user, $movie, MovieStatus::Watched, $now));
                } elseif (MovieStatus::Want === $existing[$movie->id]->getStatus()) {
                    $existing[$movie->id]->markWatched($now);
                }
            }
        });

        return $this->list($user);
    }

    private function insert(WatchedMovie $movie): void
    {
        $this->em->persist($movie);
        try {
            $this->em->flush();
        } catch (UniqueConstraintViolationException) {
            // A parallel request added it first: the end result is the same, so carry on
            $this->em->clear();
        }
    }
}
