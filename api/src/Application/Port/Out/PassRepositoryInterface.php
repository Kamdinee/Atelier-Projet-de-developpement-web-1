<?php

declare(strict_types=1);

namespace Nrv\Application\Port\Out;

use Nrv\Domain\Entity\Pass;

// port de sortie : formules de pass (Pass 3 jours)
interface PassRepositoryInterface
{
    /** @return list<Pass> */
    public function lister(): array;
}
