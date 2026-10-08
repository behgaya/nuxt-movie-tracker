<?php

namespace App\Controller;

use App\Dto\BulkInput;
use App\Dto\FolderInput;
use App\Dto\MovieInput;
use App\Entity\Folder;
use App\Entity\User;
use App\Service\Folders;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Folders: named lists of movies. Every change answers with the user's whole folder list.
 *
 * GET /api/folders/{id} is open to everyone (see access_control): FolderVoter lets anyone view
 * a public folder. Someone who may not view a folder gets a 404, as if it didn't exist, so
 * private folders can't be discovered by trying ids. Only after that comes the owner check (EDIT).
 */
#[Route('/api/folders')]
final class FolderController extends AbstractController
{
    public function __construct(private readonly Folders $folders)
    {
    }

    #[Route('', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function list(#[CurrentUser] User $user): JsonResponse
    {
        return $this->json($this->folders->list($user));
    }

    #[Route('', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function create(
        #[CurrentUser] User $user,
        #[MapRequestPayload(acceptFormat: 'json', validationFailedStatusCode: 400)] FolderInput $input,
    ): JsonResponse {
        return $this->json($this->folders->create($user, $input));
    }

    #[Route('/{id<\d+>}', methods: ['GET'])]
    #[IsGranted('VIEW', 'folder', message: 'Folder not found', statusCode: 404)]
    public function show(
        #[MapEntity(message: 'Folder not found')] Folder $folder,
        #[CurrentUser] ?User $user,
    ): JsonResponse {
        return $this->json($folder->toArray($user));
    }

    #[Route('/{id<\d+>}', methods: ['PUT'])]
    #[IsGranted('VIEW', 'folder', message: 'Folder not found', statusCode: 404)]
    #[IsGranted('EDIT', 'folder', message: 'Only the owner can change this folder')]
    public function update(
        #[MapEntity(message: 'Folder not found')] Folder $folder,
        #[MapRequestPayload(acceptFormat: 'json', validationFailedStatusCode: 400)] FolderInput $input,
    ): JsonResponse {
        return $this->json($this->folders->update($folder, $input));
    }

    #[Route('/{id<\d+>}', methods: ['DELETE'])]
    #[IsGranted('VIEW', 'folder', message: 'Folder not found', statusCode: 404)]
    #[IsGranted('EDIT', 'folder', message: 'Only the owner can change this folder')]
    public function delete(#[MapEntity(message: 'Folder not found')] Folder $folder): JsonResponse
    {
        return $this->json($this->folders->delete($folder));
    }

    #[Route('/{id<\d+>}/movies', methods: ['POST'])]
    #[IsGranted('VIEW', 'folder', message: 'Folder not found', statusCode: 404)]
    #[IsGranted('EDIT', 'folder', message: 'Only the owner can change this folder')]
    public function addMovie(
        #[MapEntity(message: 'Folder not found')] Folder $folder,
        #[MapRequestPayload(acceptFormat: 'json', validationFailedStatusCode: 400)] MovieInput $movie,
    ): JsonResponse {
        return $this->json($this->folders->addMovie($folder, $movie));
    }

    /** Many movies at once, e.g. from selection mode. Moving to another folder is a bulk add there plus a bulk remove here. */
    #[Route('/{id<\d+>}/movies/bulk', methods: ['POST'])]
    #[IsGranted('VIEW', 'folder', message: 'Folder not found', statusCode: 404)]
    #[IsGranted('EDIT', 'folder', message: 'Only the owner can change this folder')]
    public function bulk(
        #[MapEntity(message: 'Folder not found')] Folder $folder,
        #[MapRequestPayload(acceptFormat: 'json', validationFailedStatusCode: 400)] BulkInput $input,
    ): JsonResponse {
        return $this->json($this->folders->bulk($folder, $input));
    }

    #[Route('/{id<\d+>}/movies/{tmdbId<\d+>}', methods: ['DELETE'])]
    #[IsGranted('VIEW', 'folder', message: 'Folder not found', statusCode: 404)]
    #[IsGranted('EDIT', 'folder', message: 'Only the owner can change this folder')]
    public function removeMovie(#[MapEntity(id: 'id', message: 'Folder not found')] Folder $folder, int $tmdbId): JsonResponse
    {
        return $this->json($this->folders->removeMovie($folder, $tmdbId));
    }
}
