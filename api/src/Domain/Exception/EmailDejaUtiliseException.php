<?php

declare(strict_types=1);

namespace Nrv\Domain\Exception;

final class EmailDejaUtiliseException extends NrvException
{
    public function __construct()
    {
        parent::__construct('un compte existe deja avec cette adresse email');
    }
}
