<?php

declare(strict_types=1);

namespace Nrv\Domain\ValueObject;

// colonne commandes.statut
enum StatutCommande: string
{
    case EnAttente = 'EN_ATTENTE';
    case Payee = 'PAYEE';
    case Annulee = 'ANNULEE';
}
