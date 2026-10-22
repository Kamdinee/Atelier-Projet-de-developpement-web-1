<?php

declare(strict_types=1);

namespace Nrv\Tests\Application\Mock;

use Nrv\Application\Port\Out\JaugeRepositoryInterface;
use Nrv\Domain\ValueObject\JaugeSoiree;

final class InMemoryJaugeRepository implements JaugeRepositoryInterface
{
    /**
     * @param list<JaugeSoiree> $jauges
     */
    public function __construct(private readonly array $jauges)
    {
    }

    public function toutes(): array
    {
        return $this->jauges;
    }
}
