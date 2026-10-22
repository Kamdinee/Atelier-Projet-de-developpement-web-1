<?php

declare(strict_types=1);

namespace Nrv\Application\UseCase;

use Nrv\Application\Port\Out\PassRepositoryInterface;

final class ListerPass
{
    public function __construct(
        private readonly PassRepositoryInterface $passRepository,
    ) {
    }

    public function executer(): array
    {
        return $this->passRepository->lister();
    }
}
