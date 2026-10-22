<?php

declare(strict_types=1);

namespace Nrv\Tests\Application\Mock;

use Closure;
use Nrv\Application\Port\Out\TransactionInterface;

// transaction en memoire : execute simplement le travail
final class FausseTransaction implements TransactionInterface
{
    public int $executions = 0;

    public function executer(Closure $travail): mixed
    {
        $this->executions++;

        return $travail();
    }
}
