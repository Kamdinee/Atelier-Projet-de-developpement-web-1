<?php

declare(strict_types=1);

namespace Nrv\Application\Port\In;

use Nrv\Application\Dto\CommandeDetailDto;

// cas d'usage : une commande de l'utilisateur connecte
interface ConsulterCommandeInterface
{
    public function executer(int $utilisateurId, string $commandeId): CommandeDetailDto;
}
