<?php

namespace App\Controller;

use App\Dto\MovieInput;
use App\Entity\User;
use App\Enum\MovieStatus;
use App\Service\WatchList;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * The logged-in user's want-to-watch list. Every route answers with the whole updated list.
 * Marking a wanted movie as watched (POST /api/watched) moves it out of this list.
 */
#[Route('/api/want')]
#[IsGranted('ROLE_USER')]
final class WantController extends AbstractController
{
    public function __construct(private readonly WatchList $watchList)
    {
    }

    #[Route('', methods: ['GET'])]
    public function list(#[CurrentUser] User $user): JsonResponse
    {
        return $this->json($this->watchList->list($user, MovieStatus::Want));
    }

    #[Route('', methods: ['POST'])]
    public function add(
        #[CurrentUser] User $user,
        #[MapRequestPayload(acceptFormat: 'json', validationFailedStatusCode: 400)] MovieInput $movie,
    ): JsonResponse {
        return $this->json($this->watchList->want($user, $movie));
    }

    #[Route('/{id<\d+>}', methods: ['DELETE'])]
    public function remove(#[CurrentUser] User $user, int $id): JsonResponse
    {
        return $this->json($this->watchList->remove($user, $id, MovieStatus::Want));
    }
}
