<?php

declare(strict_types=1);

namespace Nrv\Application\Port\In;

use Nrv\Application\Dto\SessionDto;

// cas d'usage : se connecter et obtenir un jeton
interface AuthentifierUtilisateurInterface
{
    public function executer(string $email, string $motDePasse): SessionDto;
}
