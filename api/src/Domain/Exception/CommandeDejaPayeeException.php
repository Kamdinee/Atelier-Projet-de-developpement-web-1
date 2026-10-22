<?php

declare(strict_types=1);

namespace Nrv\Domain\Exception;

final class CommandeDejaPayeeException extends NrvException
{
    public function __construct()
    {
        parent::__construct("la commande a deja ete payee ou annulee");
    }
}
