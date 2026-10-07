<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\Recommender;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/** Movies recommended from the logged-in user's watched list */
#[Route('/api/recommendations')]
#[IsGranted('ROLE_USER')]
final class RecommendationController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function list(#[CurrentUser] User $user, Recommender $recommender): JsonResponse
    {
        return $this->json($recommender->for($user));
    }
}
