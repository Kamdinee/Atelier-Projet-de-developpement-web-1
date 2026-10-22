<?php

declare(strict_types=1);

namespace Nrv\Application\Dto;

// resultat du paiement : la commande payee et les billets emis
final class PaiementDto
{
    /**
     * @param list<BilletDto> $billets
     */
    public function __construct(
        public readonly CommandeDetailDto $commande,
        public readonly array $billets,
    ) {
    }
}
