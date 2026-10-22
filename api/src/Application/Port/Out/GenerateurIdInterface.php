<?php

declare(strict_types=1);

namespace Nrv\Application\Port\Out;

// port de sortie : identifiants aleatoires (uuid v4) des commandes et des jetons de billets
interface GenerateurIdInterface
{
    public function uuid(): string;
}
