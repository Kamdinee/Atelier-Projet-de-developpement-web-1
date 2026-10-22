<?php

declare(strict_types=1);

namespace Nrv\Infrastructure\Persistence\Pdo;

use Closure;
use Nrv\Application\Port\Out\TransactionInterface;
use PDO;
use Throwable;

// adaptateur du port TransactionInterface : transaction innodb (commit ou rollback)
final class PdoTransaction implements TransactionInterface
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function executer(Closure $travail): mixed
    {
        $this->pdo->beginTransaction();
        try {
            $resultat = $travail();
            $this->pdo->commit();

            return $resultat;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
