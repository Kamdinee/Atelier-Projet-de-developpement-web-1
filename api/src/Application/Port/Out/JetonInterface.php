<?php

declare(strict_types=1);

namespace Nrv\Application\Port\Out;

use Nrv\Domain\Entity\Utilisateur;

// port de sortie : jeton d'authentification (jwt dans l'infrastructure)
interface JetonInterface
{
    public function creer(Utilisateur $utilisateur): string;

    // date d'expiration d'un jeton cree a l'instant (format iso 8601)
    public function expiration(): string;
}
