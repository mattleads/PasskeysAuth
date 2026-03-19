<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register/password', name: 'app_register_password', methods: ['GET', 'POST'])]
    public function registerPassword(Request $request, UserRepository $userRepository, UserPasswordHasherInterface $passwordHasher): Response
    {
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $password = $request->request->get('password');

            if ($email && $password) {

                $dummyUser = new User();
                $hashedPassword = $passwordHasher->hashPassword($dummyUser, $password);

                $userRepository->createPasswordUser($email, $hashedPassword);

                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('app/register_password.html.twig');
    }
}
