<?php

declare(strict_types=1);

namespace Nrv\Tests\Application\Mock;

use Nrv\Application\Port\Out\GenerateurIdInterface;

// uuid v4 previsibles : ...0001, ...0002
final class FauxGenerateurId implements GenerateurIdInterface
{
    private int $n = 0;

    public function uuid(): string
    {
        return sprintf('00000000-0000-4000-8000-%012d', ++$this->n);
    }
}
