<?php

declare(strict_types=1);

namespace Nrv\Application\Port\In;

use Nrv\Application\Dto\CommandeDetailDto;
use Nrv\Application\Dto\LigneDemandeeDto;

// cas d'usage : transformer le panier en commande "en attente" (prix recalcules par le serveur)
interface CreerCommandeInterface
{
    /** @param list<LigneDemandeeDto> $lignes */
    public function executer(int $utilisateurId, array $lignes): CommandeDetailDto;
}
