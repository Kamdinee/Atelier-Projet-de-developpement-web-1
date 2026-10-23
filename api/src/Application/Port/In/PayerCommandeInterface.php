<?php

declare(strict_types=1);

namespace Nrv\Application\Port\In;

use Nrv\Application\Dto\CarteDto;
use Nrv\Application\Dto\PaiementDto;

// cas d'usage : paiement simule, reservation des places et emission des billets (tout ou rien)
interface PayerCommandeInterface
{
    public function executer(int $utilisateurId, string $commandeId, CarteDto $saisie): PaiementDto;
}
