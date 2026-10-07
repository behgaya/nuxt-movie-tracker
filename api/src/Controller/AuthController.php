<?php

namespace App\Controller;

use App\Dto\CredentialsInput;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/auth')]
final class AuthController extends AbstractController
{
    #[Route('/register', methods: ['POST'])]
    public function register(
        #[MapRequestPayload(acceptFormat: 'json', validationFailedStatusCode: 400)] CredentialsInput $input,
        UserRepository $users,
        UserPasswordHasherInterface $hasher,
        EntityManagerInterface $em,
        Security $security,
    ): JsonResponse {
        if ($users->findOneBy(['username' => $input->username])) {
            throw new ConflictHttpException('That username is already taken');
        }

        $user = new User($input->username);
        $user->setPassword($hasher->hashPassword($user, $input->password));
        $em->persist($user);
        $em->flush();

        $security->login($user, 'json_login', 'main');

        return $this->json(['username' => $user->getUsername()]);
    }

    /**
     * The json_login firewall checks the username and password before this runs.
     * Wrong credentials never reach here: AuthenticationFailureHandler answers with a 401.
     */
    #[Route('/login', methods: ['POST'])]
    public function login(#[CurrentUser] User $user): JsonResponse
    {
        return $this->json(['username' => $user->getUsername()]);
    }

    /** Who is logged in, or null. Used by the Nuxt app on startup. */
    #[Route('/me', methods: ['GET'])]
    public function me(#[CurrentUser] ?User $user): JsonResponse
    {
        return $this->json(['user' => $user ? ['username' => $user->getUsername()] : null]);
    }
}
