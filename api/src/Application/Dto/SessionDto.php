<?php

declare(strict_types=1);

namespace Nrv\Application\Dto;

// resultat d'une connexion : le jeton et le compte
final class SessionDto
{
    public function __construct(
        public readonly string $jeton,
        public readonly string $expireLe,
        public readonly UtilisateurDto $utilisateur,
    ) {
    }
}
