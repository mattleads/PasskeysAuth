<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;

class RecoveryLoginController extends AbstractController
{
    #[Route('/recovery-login', name: 'app_recovery_login')]
    public function login(
        Request $request,
        UserRepository $userRepository,
        PasswordHasherFactoryInterface $hasherFactory,
        Security $security,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_settings_passkeys_index');
        }

        $error = null;

        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $submittedCode = $request->request->get('code');

            if ($email && $submittedCode) {
                $user = $userRepository->findOneBy(['email' => $email]);

                if ($user) {
                    $hasher = $hasherFactory->getPasswordHasher(User::class);
                    $matchedRecoveryCodeEntity = null;

                    foreach ($user->getRecoveryCodes() as $recoveryCode) {
                        if ($hasher->verify($recoveryCode->getHashedCode(), $submittedCode)) {
                            $matchedRecoveryCodeEntity = $recoveryCode;
                            break;
                        }
                    }

                    if ($matchedRecoveryCodeEntity) {
                        // 1. Authenticate the user
                        $security->login($user, \App\Security\HybridAuthenticator::class);

                        // 2. Burn the code
                        $entityManager->remove($matchedRecoveryCodeEntity);
                        $entityManager->flush();

                        return $this->redirectToRoute('app_settings_passkeys_index');
                    } else {
                        $error = 'Invalid email or recovery code.';
                    }
                } else {
                    $error = 'Invalid email or recovery code.';
                }
            } else {
                $error = 'Please provide both email and recovery code.';
            }
        }

        return $this->render('app/recovery_login.html.twig', [
            'error' => $error,
        ]);
    }
}
