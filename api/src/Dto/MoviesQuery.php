<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/** Query string of GET /api/movies */
final class MoviesQuery
{
    public function __construct(
        public readonly string $q = '',
        // TMDB only serves pages 1–500
        #[Assert\Range(min: 1, max: 500, notInRangeMessage: 'page must be an integer between 1 and 500')]
        public readonly int $page = 1,
    ) {
    }
}
