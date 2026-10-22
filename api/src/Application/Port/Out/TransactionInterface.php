<?php

declare(strict_types=1);

namespace Nrv\Application\Port\Out;

use Closure;

// port de sortie : tout ou rien ; une exception dans le travail annule tout ce qu'il a ecrit
interface TransactionInterface
{
    /**
     * @template T
     * @param Closure(): T $travail
     * @return T
     */
    public function executer(Closure $travail): mixed;
}
