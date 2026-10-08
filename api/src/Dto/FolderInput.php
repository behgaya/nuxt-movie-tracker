<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/** Body of POST /api/folders and PUT /api/folders/{id}. The name is trimmed before it is validated. */
final class FolderInput
{
    #[Assert\NotBlank(message: 'name must not be empty')]
    #[Assert\Length(max: 50, maxMessage: 'name must be at most 50 characters')]
    public readonly string $name;

    public function __construct(
        string $name = '',
        public readonly bool $isPublic = false,
    ) {
        $this->name = trim($name);
    }
}
