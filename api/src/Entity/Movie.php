<?php

namespace App\Entity;

use App\Dto\MovieInput;
use App\Repository\MovieRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * A TMDB movie that is in at least one folder. One row per movie, shared by every folder
 * and every user, so a movie in 50 folders is stored once.
 */
#[ORM\Entity(repositoryClass: MovieRepository::class)]
class Movie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(unique: true)]
    private int $tmdbId;

    #[ORM\Column(length: 255)]
    private string $title;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $posterPath;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $releaseDate;

    public function __construct(MovieInput $movie)
    {
        $this->tmdbId = $movie->id;
        $this->title = trim($movie->title);
        $this->posterPath = $movie->posterPath;
        $this->releaseDate = $movie->releaseDate;
    }

    public function getTmdbId(): int
    {
        return $this->tmdbId;
    }

    public function getPosterPath(): ?string
    {
        return $this->posterPath;
    }

    /** Same shape as the movies in the Nuxt app, so MovieCard can show it */
    public function toArray(): array
    {
        return array_filter([
            'id' => $this->tmdbId,
            'title' => $this->title,
            'poster_path' => $this->posterPath,
            'release_date' => $this->releaseDate,
        ], fn ($value, $key) => null !== $value || 'poster_path' === $key, \ARRAY_FILTER_USE_BOTH);
    }
}
