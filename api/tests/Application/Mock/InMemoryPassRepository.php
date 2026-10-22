<?php

declare(strict_types=1);

namespace Nrv\Tests\Application\Mock;

use Nrv\Application\Port\Out\PassRepositoryInterface;
use Nrv\Domain\Entity\Pass;

final class InMemoryPassRepository implements PassRepositoryInterface
{
    /** @param list<Pass> $pass */
    public function __construct(private readonly array $pass)
    {
    }

    public function lister(): array
    {
        return $this->pass;
    }
}
