<?php

declare(strict_types=1);

namespace Nrv\Application\UseCase;

use Nrv\Application\Dto\SoireeResumeDto;
use Nrv\Application\Port\In\ListerSoireesInterface;
use Nrv\Application\Port\Out\SoireeRepositoryInterface;
use Nrv\Domain\Entity\Soiree;

final class ListerSoirees implements ListerSoireesInterface
{
    public function __construct(private readonly SoireeRepositoryInterface $soirees)
    {
    }

    public function executer(): array
    {
        return array_map(static fn (Soiree $s): SoireeResumeDto => SoireeResumeDto::depuis($s), $this->soirees->lister());
    }
}
