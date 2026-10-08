<?php

namespace App\Service;

use App\Dto\BulkInput;
use App\Dto\FolderInput;
use App\Dto\MovieInput;
use App\Entity\Folder;
use App\Entity\Movie;
use App\Entity\User;
use App\Repository\FolderRepository;
use App\Repository\MovieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * A user's folders. Every change returns the user's whole updated folder list (summaries),
 * as the frontend expects. Who may change which folder is checked before, by FolderVoter.
 */
final class Folders
{
    public function __construct(
        private readonly FolderRepository $folders,
        private readonly MovieRepository $movies,
        private readonly EntityManagerInterface $em,
    ) {
    }

    /** @return list<array> by name */
    public function list(User $user): array
    {
        return array_map(fn (Folder $f) => $f->toSummary(), $this->folders->listFor($user));
    }

    public function create(User $user, FolderInput $input): array
    {
        $this->assertNameIsFree($user, $input->name);
        $this->em->persist(new Folder($user, $input->name, $input->isPublic));
        $this->em->flush();

        return $this->list($user);
    }

    public function update(Folder $folder, FolderInput $input): array
    {
        $this->assertNameIsFree($folder->getOwner(), $input->name, $folder);
        $folder->update($input->name, $input->isPublic);
        $this->em->flush();

        return $this->list($folder->getOwner());
    }

    public function delete(Folder $folder): array
    {
        $this->em->remove($folder);
        $this->em->flush();

        return $this->list($folder->getOwner());
    }

    /** Adding a movie twice does nothing. The Movie row is shared, so it is created only the first time. */
    public function addMovie(Folder $folder, MovieInput $input): array
    {
        return $this->bulk($folder, new BulkInput(add: [$input]));
    }

    /** Adds and removes many movies in one transaction: either all changes apply or none do */
    public function bulk(Folder $folder, BulkInput $input): array
    {
        $this->em->wrapInTransaction(function () use ($folder, $input) {
            foreach ($input->remove as $tmdbId) {
                $folder->removeMovie($tmdbId);
            }

            // One query for the movies already stored, instead of one per movie
            $known = [];
            foreach ($this->movies->findBy(['tmdbId' => array_map(fn (MovieInput $m) => $m->id, $input->add)]) as $movie) {
                $known[$movie->getTmdbId()] = $movie;
            }
            foreach ($input->add as $movie) {
                if (!isset($known[$movie->id])) {
                    $this->em->persist($known[$movie->id] = new Movie($movie));
                }
                $folder->addMovie($known[$movie->id]);
            }
        });

        return $this->list($folder->getOwner());
    }

    public function removeMovie(Folder $folder, int $tmdbId): array
    {
        $folder->removeMovie($tmdbId);
        $this->em->flush();

        return $this->list($folder->getOwner());
    }

    /** Names are unique per user (uniq_owner_folder_name), so say so clearly instead of failing on the constraint */
    private function assertNameIsFree(User $user, string $name, ?Folder $except = null): void
    {
        $same = $this->folders->findOneBy(['owner' => $user, 'name' => $name]);
        if ($same && $same !== $except) {
            throw new ConflictHttpException('You already have a folder with that name');
        }
    }
}
