<?php

declare(strict_types=1);

namespace Nrv\Application\Port\Out;

use Nrv\Domain\Exception\PaiementRefuseException;
use Nrv\Domain\ValueObject\CarteBancaire;

// port de sortie : la banque (simulee dans l'infrastructure)
interface PasserelleBancaireInterface
{
    /** @throws PaiementRefuseException */
    public function debiter(CarteBancaire $carte, int $montantCentimes): void;
}
