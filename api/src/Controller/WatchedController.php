<?php

namespace App\Controller;

use App\Dto\BulkInput;
use App\Dto\MovieInput;
use App\Dto\ReviewInput;
use App\Entity\User;
use App\Service\WatchList;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/** The logged-in user's watched list. Every route answers with the whole updated list. */
#[Route('/api/watched')]
#[IsGranted('ROLE_USER')]
final class WatchedController extends AbstractController
{
    public function __construct(private readonly WatchList $watchList)
    {
    }

    #[Route('', methods: ['GET'])]
    public function list(#[CurrentUser] User $user): JsonResponse
    {
        return $this->json($this->watchList->list($user));
    }

    #[Route('', methods: ['POST'])]
    public function add(
        #[CurrentUser] User $user,
        #[MapRequestPayload(acceptFormat: 'json', validationFailedStatusCode: 400)] MovieInput $movie,
    ): JsonResponse {
        return $this->json($this->watchList->add($user, $movie));
    }

    #[Route('/{id<\d+>}', methods: ['PATCH'])]
    public function review(
        #[CurrentUser] User $user,
        int $id,
        #[MapRequestPayload(acceptFormat: 'json', validationFailedStatusCode: 400)] ReviewInput $input,
    ): JsonResponse {
        return $this->json($this->watchList->review($user, $id, $input));
    }

    #[Route('/{id<\d+>}', methods: ['DELETE'])]
    public function remove(#[CurrentUser] User $user, int $id): JsonResponse
    {
        return $this->json($this->watchList->remove($user, $id));
    }

    #[Route('/bulk', methods: ['POST'])]
    public function bulk(
        #[CurrentUser] User $user,
        #[MapRequestPayload(acceptFormat: 'json', validationFailedStatusCode: 400)] BulkInput $input,
    ): JsonResponse {
        return $this->json($this->watchList->bulk($user, $input));
    }
}
