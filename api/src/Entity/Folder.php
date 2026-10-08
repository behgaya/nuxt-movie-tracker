<?php

namespace App\Entity;

use App\Repository\FolderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * A named list of movies made by a user, like "Favorites" or "Horror night".
 * Private unless isPublic: then anyone with the link can view it, but only the owner can change it (FolderVoter).
 */
#[ORM\Entity(repositoryClass: FolderRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_owner_folder_name', columns: ['owner_id', 'name'])]
class Folder
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $owner;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    /**
     * ManyToMany: a folder holds many movies, and a movie can be in many folders.
     * Doctrine keeps the pairs in a join table (folder_movie) that has no entity of its own.
     *
     * @var Collection<int, Movie>
     */
    #[ORM\ManyToMany(targetEntity: Movie::class)]
    #[ORM\JoinTable(name: 'folder_movie')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(onDelete: 'CASCADE')]
    #[ORM\OrderBy(['title' => 'ASC'])]
    private Collection $movies;

    public function __construct(
        User $owner,
        #[ORM\Column(length: 50)]
        private string $name,
        #[ORM\Column]
        private bool $isPublic = false,
    ) {
        $this->owner = $owner;
        $this->createdAt = new \DateTimeImmutable();
        $this->movies = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOwner(): User
    {
        return $this->owner;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isPublic(): bool
    {
        return $this->isPublic;
    }

    public function update(string $name, bool $isPublic): void
    {
        $this->name = $name;
        $this->isPublic = $isPublic;
    }

    public function addMovie(Movie $movie): void
    {
        if (!$this->movies->contains($movie)) {
            $this->movies->add($movie);
        }
    }

    public function removeMovie(int $tmdbId): void
    {
        foreach ($this->movies as $movie) {
            if ($movie->getTmdbId() === $tmdbId) {
                $this->movies->removeElement($movie);
            }
        }
    }

    /** For the folders page and the "Add to folder" menu: movieIds says which movies it holds */
    public function toSummary(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'isPublic' => $this->isPublic,
            'movieIds' => $this->movies->map(fn (Movie $m) => $m->getTmdbId())->getValues(),
            // Up to 4 posters for the folder's cover
            'posters' => \array_slice(array_values(array_filter($this->movies->map(fn (Movie $m) => $m->getPosterPath())->getValues())), 0, 4),
        ];
    }

    /** The folder page. isOwner tells the page whether to show the edit buttons. */
    public function toArray(?User $viewer): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'isPublic' => $this->isPublic,
            'owner' => $this->owner->getUsername(),
            'isOwner' => $viewer === $this->owner,
            'movies' => $this->movies->map(fn (Movie $m) => $m->toArray())->getValues(),
        ];
    }
}
