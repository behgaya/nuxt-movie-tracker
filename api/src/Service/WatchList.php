<?php

namespace App\Service;

use App\Dto\BulkInput;
use App\Dto\MovieInput;
use App\Dto\ReviewInput;
use App\Entity\User;
use App\Entity\WatchedMovie;
use App\Repository\WatchedMovieRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** A user's watched list. Every method returns the whole updated list, as the frontend expects. */
final class WatchList
{
    public function __construct(
        private readonly WatchedMovieRepository $movies,
        private readonly EntityManagerInterface $em,
    ) {
    }

    /** @return list<array> newest first */
    public function list(User $user): array
    {
        return array_map(fn (WatchedMovie $m) => $m->toArray(), $this->movies->listFor($user));
    }

    public function add(User $user, MovieInput $movie): array
    {
        if (!$this->movies->findOneFor($user, $movie->id)) {
            $this->em->persist(new WatchedMovie($user, $movie, new \DateTimeImmutable()));
            try {
                $this->em->flush();
            } catch (UniqueConstraintViolationException) {
                // A parallel request added it first: the end result is the same, so carry on
                $this->em->clear();
            }
        }

        return $this->list($user);
    }

    public function remove(User $user, int $tmdbId): array
    {
        $this->movies->removeFor($user, [$tmdbId]);

        return $this->list($user);
    }

    public function review(User $user, int $tmdbId, ReviewInput $input): array
    {
        // Only watched movies can be reviewed
        $movie = $this->movies->findOneFor($user, $tmdbId)
            ?? throw new NotFoundHttpException('Movie is not in the watched list');

        $movie->setReview($input->rating, $input->review);
        $this->em->flush();

        return $this->list($user);
    }

    /** Adds and removes many movies in one transaction: either all changes apply or none do */
    public function bulk(User $user, BulkInput $input): array
    {
        $this->em->wrapInTransaction(function () use ($user, $input) {
            $this->movies->removeFor($user, $input->remove);

            $existing = array_flip(array_map(fn (WatchedMovie $m) => $m->getTmdbId(), $this->movies->listFor($user)));
            $watchedAt = new \DateTimeImmutable();
            foreach ($input->add as $movie) {
                if (!isset($existing[$movie->id])) {
                    $this->em->persist(new WatchedMovie($user, $movie, $watchedAt));
                    $existing[$movie->id] = true;
                }
            }
        });

        return $this->list($user);
    }
}
