<?php

declare(strict_types=1);

namespace Nrv\Application\Port\In;

use Nrv\Application\Dto\UtilisateurDto;

// cas d'usage : afficher le compte de l'utilisateur connecte
interface ConsulterCompteInterface
{
    public function executer(int $utilisateurId): UtilisateurDto;
}
