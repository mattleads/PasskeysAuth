<?php

namespace App\Controller;

use App\Repository\PublicKeyCredentialSourceRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AuthController extends AbstractController
{
    #[Route('/', name: 'app_login')]
    public function index(): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('app/login.html.twig');
    }

    #[Route('/login', name: 'app_password_login', methods: ['POST'])]
    public function login(): Response
    {
        throw new \LogicException('This method will be intercepted by the login key on your firewall.');
    }

    #[Route('/api/auth/flow', name: 'app_api_auth_flow', methods: ['GET'])]
    public function apiAuthFlow(Request $request, UserRepository $userRepository, PublicKeyCredentialSourceRepository $credentialSourceRepository): JsonResponse
    {
        $email = $request->query->get('email');
        if (!$email) {
            return new JsonResponse(['flow' => 'password']);
        }

        $user = $userRepository->findOneBy(['email' => $email]);
        if (!$user) {
            return new JsonResponse(['flow' => 'password']);
        }

        // Check if user has passkeys registered
        $credentials = $credentialSourceRepository->findBy(['userHandle' => $user->getUserHandle()]);

        return new JsonResponse(['flow' => count($credentials) > 0 ? 'passkey' : 'password']);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method will be intercepted by the logout key on your firewall.');
    }
}
