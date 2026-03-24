<?php

namespace App\EventSubscriber;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Webauthn\Event\AuthenticatorAssertionResponseValidationSucceededEvent;
use App\Entity\PublicKeyCredentialSource;

readonly class PasskeyUsageSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            AuthenticatorAssertionResponseValidationSucceededEvent::class => 'onPasskeyUsed',
        ];
    }

    public function onPasskeyUsed(AuthenticatorAssertionResponseValidationSucceededEvent $event): void
    {
        $credentialSource = $event->publicKeyCredentialSource;

        if ($credentialSource instanceof PublicKeyCredentialSource) {
            $credentialSource->setLastUsedAt(new \DateTimeImmutable());

            // Persist the updated usage timestamp to the database
            $this->entityManager->persist($credentialSource);
            $this->entityManager->flush();
        }
    }
}

