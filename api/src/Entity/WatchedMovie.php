<?php

namespace App\Entity;

use App\Dto\MovieInput;
use App\Enum\MovieStatus;
use App\Repository\WatchedMovieRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * One movie in one of a user's lists: watched, or want to watch (see status).
 * A movie is in at most one list per user; watching a wanted movie moves it over. tmdbId is the id the frontend knows the movie by.
 */
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

    #[ORM\Column(length: 10, enumType: MovieStatus::class)]
    private MovieStatus $status;

    /** When the movie was added to either list */
    #[ORM\Column]
    private \DateTimeImmutable $addedAt;

    /** null while the movie is only wanted */
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $watchedAt;

    #[ORM\Column(type: 'smallint', nullable: true)]
    private ?int $rating = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $review = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $reviewedAt = null;

    public function __construct(User $user, MovieInput $movie, MovieStatus $status, \DateTimeImmutable $addedAt)
    {
        $this->user = $user;
        $this->tmdbId = $movie->id;
        $this->title = trim($movie->title);
        $this->posterPath = $movie->posterPath;
        $this->releaseDate = $movie->releaseDate;
        $this->status = $status;
        $this->addedAt = $addedAt;
        $this->watchedAt = MovieStatus::Watched === $status ? $addedAt : null;
    }

    public function getTmdbId(): int
    {
        return $this->tmdbId;
    }

    public function getStatus(): MovieStatus
    {
        return $this->status;
    }

    /** Moves a wanted movie to the watched list */
    public function markWatched(\DateTimeImmutable $watchedAt): void
    {
        $this->status = MovieStatus::Watched;
        $this->watchedAt = $watchedAt;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getRating(): ?int
    {
        return $this->rating;
    }

    /** null or '' clears the field */
    public function setReview(?int $rating, ?string $review): void
    {
        $this->rating = $rating;
        $this->review = $review ?: null;
        $this->reviewedAt = new \DateTimeImmutable();
    }

    /** Same shape as the WatchedMovie or WantedMovie type in the Nuxt app (shared/types/movie.ts); empty optional fields are left out */
    public function toArray(): array
    {
        return array_filter([
            'id' => $this->tmdbId,
            'title' => $this->title,
            'poster_path' => $this->posterPath,
            'release_date' => $this->releaseDate,
            'watchedAt' => $this->watchedAt?->format(\DATE_ATOM),
            // Only the want list needs it: the watched list shows watchedAt instead
            'addedAt' => MovieStatus::Want === $this->status ? $this->addedAt->format(\DATE_ATOM) : null,
            'rating' => $this->rating,
            'review' => $this->review,
            'reviewedAt' => $this->reviewedAt?->format(\DATE_ATOM),
        ], fn ($value, $key) => null !== $value || 'poster_path' === $key, \ARRAY_FILTER_USE_BOTH);
    }
}
