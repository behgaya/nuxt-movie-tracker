<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\WatchedMovie;
use App\Enum\MovieStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<WatchedMovie> */
class WatchedMovieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WatchedMovie::class);
    }

    /** @return list<WatchedMovie> newest first: by watch date for the watched list, by add date for the want list */
    public function listFor(User $user, MovieStatus $status): array
    {
        $date = MovieStatus::Watched === $status ? 'watchedAt' : 'addedAt';

        return $this->findBy(['user' => $user, 'status' => $status], [$date => 'DESC', 'id' => 'DESC']);
    }

    /** With no status, finds the movie in either list */
    public function findOneFor(User $user, int $tmdbId, ?MovieStatus $status = null): ?WatchedMovie
    {
        return $this->findOneBy(['user' => $user, 'tmdbId' => $tmdbId] + ($status ? ['status' => $status] : []));
    }

    /** @param list<int> $tmdbIds */
    public function removeFor(User $user, MovieStatus $status, array $tmdbIds): void
    {
        if (!$tmdbIds) {
            return;
        }

        $this->createQueryBuilder('m')
            ->delete()
            ->where('m.user = :user AND m.status = :status AND m.tmdbId IN (:ids)')
            ->setParameter('user', $user)
            ->setParameter('status', $status)
            ->setParameter('ids', $tmdbIds)
            ->getQuery()
            ->execute();
    }
}
