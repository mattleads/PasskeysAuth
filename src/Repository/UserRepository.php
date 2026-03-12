<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;
use Webauthn\Bundle\Repository\CanGenerateUserEntity;
use Webauthn\Bundle\Repository\CanRegisterUserEntity;
use Webauthn\Bundle\Repository\PublicKeyCredentialUserEntityRepositoryInterface;
use Webauthn\Exception\InvalidDataException;
use Webauthn\PublicKeyCredentialUserEntity;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PublicKeyCredentialUserEntityRepositoryInterface, CanRegisterUserEntity, CanGenerateUserEntity
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function saveUserEntity(PublicKeyCredentialUserEntity $userEntity): void
    {
        $user = new User();
        $user->setEmail($userEntity->name);
        $user->setUserHandle($userEntity->id);

        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * @throws InvalidDataException
     */
    public function generateUserEntity(?string $username, ?string $displayName): PublicKeyCredentialUserEntity
    {
        return new PublicKeyCredentialUserEntity(
            $username ?? '',
            Uuid::v4()->toRfc4122(),
            $displayName ?? $username ?? ''
        );
    }

    /**
     * @throws InvalidDataException
     */
    public function findOneByUsername(string $username): ?PublicKeyCredentialUserEntity
    {
        $user = $this->findOneBy(['email' => $username]);
        if (!$user) {
            return null;
        }

        return new PublicKeyCredentialUserEntity(
            $user->getEmail(),
            $user->getUserHandle(),
            $user->getEmail()
        );
    }

    /**
     * @throws InvalidDataException
     */
    public function findOneByUserHandle(string $userHandle): ?PublicKeyCredentialUserEntity
    {
        $user = $this->findOneBy(['userHandle' => $userHandle]);
        if (!$user) {
            return null;
        }

        return new PublicKeyCredentialUserEntity(
            $user->getEmail(),
            $user->getUserHandle(),
            $user->getEmail()
        );
    }
}
