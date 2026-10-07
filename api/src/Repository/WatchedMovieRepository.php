<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\WatchedMovie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<WatchedMovie> */
class WatchedMovieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WatchedMovie::class);
    }

    /** @return list<WatchedMovie> newest first */
    public function listFor(User $user): array
    {
        return $this->findBy(['user' => $user], ['watchedAt' => 'DESC', 'id' => 'DESC']);
    }

    public function findOneFor(User $user, int $tmdbId): ?WatchedMovie
    {
        return $this->findOneBy(['user' => $user, 'tmdbId' => $tmdbId]);
    }

    /** @param list<int> $tmdbIds */
    public function removeFor(User $user, array $tmdbIds): void
    {
        if (!$tmdbIds) {
            return;
        }

        $this->createQueryBuilder('m')
            ->delete()
            ->where('m.user = :user AND m.tmdbId IN (:ids)')
            ->setParameter('user', $user)
            ->setParameter('ids', $tmdbIds)
            ->getQuery()
            ->execute();
    }
}
