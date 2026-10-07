<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/** Body of POST /api/auth/register. The username is trimmed and lowercased before it is validated. */
final class CredentialsInput
{
    #[Assert\Regex('/^[a-z0-9_]{3,30}$/', message: 'Username must be 3–30 characters: letters, numbers or _')]
    public readonly string $username;

    public function __construct(
        string $username = '',
        #[Assert\Length(min: 8, max: 200, minMessage: 'Password must be at least 8 characters', maxMessage: 'Password must be at most 200 characters')]
        public readonly string $password = '',
    ) {
        $this->username = mb_strtolower(trim($username));
    }
}
