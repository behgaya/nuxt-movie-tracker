<?php

namespace App\Controller;

use App\Dto\MoviesQuery;
use App\Service\TmdbClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/** TMDB proxy. Logged-in users only, so nobody can use the TMDB key through these routes. */
#[Route('/api/movies')]
#[IsGranted('ROLE_USER')]
final class MovieController extends AbstractController
{
    public function __construct(private readonly TmdbClient $tmdb)
    {
    }

    #[Route('', methods: ['GET'])]
    public function list(#[MapQueryString(validationFailedStatusCode: 400)] MoviesQuery $query = new MoviesQuery()): JsonResponse
    {
        return $this->json($this->tmdb->movies(trim($query->q), $query->page));
    }

    #[Route('/{id<\d+>}', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        return $this->json($this->tmdb->movie($id));
    }
}
