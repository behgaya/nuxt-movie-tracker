<?php

namespace App\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

/** A movie sent by the frontend to mark as watched (only the fields we store) */
final class MovieInput
{
    public function __construct(
        #[Assert\Positive(message: 'id must be a positive integer')]
        public readonly int $id,
        #[Assert\NotBlank(message: 'title must be a non-empty string', normalizer: 'trim')]
        #[Assert\Length(max: 255)]
        public readonly string $title,
        #[SerializedName('poster_path')]
        #[Assert\Length(max: 255)]
        public readonly ?string $posterPath = null,
        #[SerializedName('release_date')]
        #[Assert\Length(max: 10)]
        public readonly ?string $releaseDate = null,
    ) {
    }
}
