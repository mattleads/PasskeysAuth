<?php

namespace App\Repository;

use App\Entity\PublicKeyCredentialSource;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;
use Webauthn\Bundle\Repository\PublicKeyCredentialSourceRepositoryInterface;
use Webauthn\Bundle\Repository\CanSaveCredentialSource;
use Webauthn\PublicKeyCredentialSource as WebauthnSource;
use Webauthn\PublicKeyCredentialUserEntity;

class PublicKeyCredentialSourceRepository extends ServiceEntityRepository implements PublicKeyCredentialSourceRepositoryInterface, CanSaveCredentialSource
{
    public function __construct(ManagerRegistry $registry, private readonly ObjectMapperInterface $objectMapper)
    {
        parent::__construct($registry, PublicKeyCredentialSource::class);
    }

    public function findOneByCredentialId(string $publicKeyCredentialId): ?WebauthnSource
    {
        return $this->findOneBy(['publicKeyCredentialId' => $publicKeyCredentialId]);
    }

    public function findAllForUserEntity(PublicKeyCredentialUserEntity $publicKeyCredentialUserEntity): array
    {
        return $this->findBy(['userHandle' => $publicKeyCredentialUserEntity->id]);
    }

    public function saveCredentialSource(WebauthnSource $publicKeyCredentialSource): void
    {
        $entity = $this->findOneBy(['publicKeyCredentialId' => base64_encode($publicKeyCredentialSource->publicKeyCredentialId)])
            ?? $this->objectMapper->map($publicKeyCredentialSource, PublicKeyCredentialSource::class);

        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();
    }
}
