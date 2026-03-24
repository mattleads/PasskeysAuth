<?php

namespace App\Controller;

use App\Repository\PublicKeyCredentialSourceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use App\Service\RecoveryCodeGenerator;

#[IsGranted('ROLE_USER')]
#[Route('/settings/passkeys', name: 'app_settings_passkeys_')]
class PasskeyManagementController extends AbstractController
{
    public function __construct(
        private readonly PublicKeyCredentialSourceRepository $credentialRepository,
        private readonly EntityManagerInterface $entityManager
    ) {}

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(RecoveryCodeGenerator $generator): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $newCodes = [];
        if ($user->getRecoveryCodes()->isEmpty()) {
            $newCodes = $generator->generateForUser($user, 10);
        }

        // We map our Symfony User to the WebAuthn User Entity
        $userEntity = $user->toWebAuthnUser();

        // Fetch all passkeys bound to this user
        $credentials = $this->credentialRepository->findAllForUserEntity($userEntity);

        return $this->render('settings/passkeys/index.html.twig', [
            'credentials' => $credentials,
            'newCodes' => $newCodes,
        ]);
    }

    #[Route('/{id}/revoke', name: 'revoke', methods: ['POST'])]
    public function revoke(string $id): Response
    {
        $credential = $this->credentialRepository->findOneBy(['id' => $id]);

        // Security Check: Ensure the credential belongs to the currently logged-in user
        if (!$credential || $credential->userHandle !== (string) $this->getUser()->getUserHandle()) {
            throw $this->createAccessDeniedException('You cannot revoke this passkey.');
        }

        $this->entityManager->remove($credential);
        $this->entityManager->flush();

        $this->addFlash('success', 'Passkey successfully revoked.');

        return $this->redirectToRoute('app_settings_passkeys_index');
    }
}
