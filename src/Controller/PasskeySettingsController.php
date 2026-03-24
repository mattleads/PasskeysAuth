<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\PublicKeyCredentialSourceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\Bundle\Service\PublicKeyCredentialCreationOptionsFactory;
use Webauthn\Bundle\Security\Storage\OptionsStorage;
use Webauthn\Bundle\Security\Storage\Item;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialDescriptor;

#[Route('/dashboard/passkey')]
class PasskeySettingsController extends AbstractController
{
    public function __construct(
        private readonly PublicKeyCredentialCreationOptionsFactory $optionsFactory,
        private readonly OptionsStorage $optionsStorage,
        private readonly AuthenticatorAttestationResponseValidator $attestationValidator,
        private readonly PublicKeyCredentialSourceRepository $credentialSourceRepository,
        private readonly SerializerInterface $serializer
    ) {
    }

    #[Route('/options', name: 'app_dashboard_passkey_options', methods: ['POST'])]
    public function options(): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->getUser();
            if (!$user) {
                return new JsonResponse(['errorMessage' => 'User not logged in'], Response::HTTP_UNAUTHORIZED);
            }

            $userEntity = $user->toWebAuthnUser();

            // Get already registered credentials to exclude them
            $excludeCredentials = $this->credentialSourceRepository->findAllForUserEntity($userEntity);
            $excludeDescriptors = array_map(
                fn ($source) => new PublicKeyCredentialDescriptor(
                    $source->type,
                    $source->publicKeyCredentialId,
                    $source->transports
                ),
                $excludeCredentials
            );

            $options = $this->optionsFactory->create(
                'default',
                $userEntity,
                $excludeDescriptors
            );

            $this->optionsStorage->store(Item::create($options, $userEntity));

            // Use the specialized WebAuthn serializer to handle binary data correctly (Base64URL encoding)
            $json = $this->serializer->serialize($options, 'json');

            return new JsonResponse($json, Response::HTTP_OK, [], true);
        } catch (\Throwable $t) {
            return new JsonResponse(['errorMessage' => $t->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/result', name: 'app_dashboard_passkey_result', methods: ['POST'])]
    public function result(Request $request): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->getUser();
            if (!$user) {
                return new JsonResponse(['errorMessage' => 'User not logged in'], Response::HTTP_UNAUTHORIZED);
            }

            $data = $request->getContent();
            /** @var PublicKeyCredential $publicKeyCredential */
            $publicKeyCredential = $this->serializer->deserialize($data, PublicKeyCredential::class, 'json');

            $response = $publicKeyCredential->response;
            if (!$response instanceof AuthenticatorAttestationResponse) {
                throw new \RuntimeException('Invalid response type');
            }

            $challenge = $response->clientDataJSON->challenge;
            $item = $this->optionsStorage->get($challenge);
            $options = $item->getPublicKeyCredentialOptions();

            $credentialSource = $this->attestationValidator->check(
                $response,
                $options,
                $request->getHost()
            );

            $this->credentialSourceRepository->saveCredentialSource($credentialSource);

            return new JsonResponse(['status' => 'ok']);
        } catch (\Throwable $t) {
            return new JsonResponse(['errorMessage' => $t->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
}
