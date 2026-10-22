<?php

declare(strict_types=1);

namespace Nrv\Application\Dto;

use Nrv\Domain\Entity\Utilisateur;

// compte envoye a l'adaptateur http (jamais le mot de passe)
final class UtilisateurDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $email,
        public readonly string $nom,
        public readonly string $prenom,
        public readonly string $role,
    ) {
    }

    public static function depuis(Utilisateur $utilisateur): self
    {
        return new self(
            (int) $utilisateur->id(),
            $utilisateur->email(),
            $utilisateur->nom(),
            $utilisateur->prenom(),
            $utilisateur->role()->value,
        );
    }
}
