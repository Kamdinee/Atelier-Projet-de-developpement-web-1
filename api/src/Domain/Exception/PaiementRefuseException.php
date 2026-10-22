<?php

declare(strict_types=1);

namespace Nrv\Domain\Exception;

final class PaiementRefuseException extends NrvException
{
    public function __construct()
    {
        parent::__construct("paiement refuse par la banque, aucune somme n'a ete debitee");
    }
}
