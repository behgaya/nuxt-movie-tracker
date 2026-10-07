<?php

namespace App\Entity;

use App\Dto\MovieInput;
use App\Repository\WatchedMovieRepository;
use Doctrine\ORM\Mapping as ORM;

/** One movie in one user's watched list. tmdbId is the id the frontend knows the movie by. */
#[ORM\Entity(repositoryClass: WatchedMovieRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_user_movie', columns: ['user_id', 'tmdb_id'])]
#[ORM\Index(name: 'idx_user_watched_at', columns: ['user_id', 'watched_at'])]
class WatchedMovie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'watched')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column]
    private int $tmdbId;

    #[ORM\Column(length: 255)]
    private string $title;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $posterPath;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $releaseDate;

    #[ORM\Column]
    private \DateTimeImmutable $watchedAt;

    #[ORM\Column(type: 'smallint', nullable: true)]
    private ?int $rating = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $review = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $reviewedAt = null;

    public function __construct(User $user, MovieInput $movie, \DateTimeImmutable $watchedAt)
    {
        $this->user = $user;
        $this->tmdbId = $movie->id;
        $this->title = trim($movie->title);
        $this->posterPath = $movie->posterPath;
        $this->releaseDate = $movie->releaseDate;
        $this->watchedAt = $watchedAt;
    }

    public function getTmdbId(): int
    {
        return $this->tmdbId;
    }

    /** null or '' clears the field */
    public function setReview(?int $rating, ?string $review): void
    {
        $this->rating = $rating;
        $this->review = $review ?: null;
        $this->reviewedAt = new \DateTimeImmutable();
    }

    /** Same shape as the WatchedMovie type in the Nuxt app (shared/types/movie.ts); empty optional fields are left out */
    public function toArray(): array
    {
        return array_filter([
            'id' => $this->tmdbId,
            'title' => $this->title,
            'poster_path' => $this->posterPath,
            'release_date' => $this->releaseDate,
            'watchedAt' => $this->watchedAt->format(\DATE_ATOM),
            'rating' => $this->rating,
            'review' => $this->review,
            'reviewedAt' => $this->reviewedAt?->format(\DATE_ATOM),
        ], fn ($value, $key) => null !== $value || 'poster_path' === $key, \ARRAY_FILTER_USE_BOTH);
    }
}
