<?php

declare(strict_types=1);

namespace Nrv\Infrastructure\Security;

// utilisateur lu dans le jeton jwt, pose sur la requete par AuthentificationMiddleware
final class UtilisateurConnecte
{
    public const ATTRIBUT = 'utilisateur';

    public function __construct(
        public readonly int $id,
        public readonly string $role,
        public readonly ?string $email = null,
    ) {
    }

    public function estAdmin(): bool
    {
        return $this->role === 'ADMIN';
    }
}
