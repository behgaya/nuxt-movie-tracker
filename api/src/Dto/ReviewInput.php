<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/** Body of PATCH /api/watched/{id}: null / '' clear the field */
final class ReviewInput
{
    #[Assert\Length(max: 1000, maxMessage: 'review must be at most 1000 characters')]
    public readonly string $review;

    public function __construct(
        #[Assert\Range(min: 1, max: 5, notInRangeMessage: 'rating must be an integer from 1 to 5, or null')]
        public readonly ?int $rating = null,
        ?string $review = '',
    ) {
        $this->review = trim($review ?? '');
    }
}
