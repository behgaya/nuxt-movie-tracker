<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/** Body of POST /api/watched/bulk and /api/folders/{id}/movies/bulk: add and/or remove many movies in one request */
final class BulkInput
{
    public const MAX_ITEMS = 500;

    /**
     * @param list<MovieInput> $add
     * @param list<int>        $remove TMDB ids
     */
    public function __construct(
        #[Assert\Valid]
        public readonly array $add = [],
        #[Assert\All([new Assert\Type('integer')])]
        public readonly array $remove = [],
    ) {
    }

    #[Assert\IsTrue(message: 'add and remove can hold at most 500 items in total')]
    public function isWithinLimit(): bool
    {
        return \count($this->add) + \count($this->remove) <= self::MAX_ITEMS;
    }
}
