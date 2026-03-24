<?php

namespace App\Service;

use App\Entity\RecoveryCode;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

readonly class RecoveryCodeGenerator
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    /**
     * @return string[] The plain-text codes to show the user
     */
    public function generateForUser(User $user, int $amount = 10): array
    {
        $plainCodes = [];

        for ($i = 0; $i < $amount; $i++) {
            // Generate a secure 8-character random string
            $code = bin2hex(random_bytes(4));
            $plainCodes[] = $code;

            $recoveryCode = new RecoveryCode();
            $user->addRecoveryCode($recoveryCode);
            
            // Hash the code before storing it in the database
            $hashed = $this->passwordHasher->hashPassword($user, $code);
            $recoveryCode->setHashedCode($hashed);

            $this->entityManager->persist($recoveryCode);
        }

        $this->entityManager->flush();

        return $plainCodes;
    }
}
