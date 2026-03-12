<?php

namespace App\Entity;

use App\Repository\PublicKeyCredentialSourceRepository;
use Doctrine\ORM\Mapping as ORM;
use Webauthn\PublicKeyCredentialSource as WebauthnSource;

#[ORM\Entity(repositoryClass: PublicKeyCredentialSourceRepository::class)]
#[ORM\Table(name: 'webauthn_credentials')]
class PublicKeyCredentialSource extends WebauthnSource
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
