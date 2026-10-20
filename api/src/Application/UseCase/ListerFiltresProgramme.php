<?php

declare(strict_types=1);

namespace Nrv\Application\UseCase;

use DateTimeImmutable;
use Nrv\Application\Dto\StyleDto;
use Nrv\Application\Port\In\ListerFiltresProgrammeInterface;
use Nrv\Domain\Entity\Style;
use Nrv\Domain\Repository\SpectacleRepositoryInterface;

final class ListerFiltresProgramme implements ListerFiltresProgrammeInterface
{
    public function __construct(private readonly SpectacleRepositoryInterface $spectacles)
    {
    }

    public function styles(): array
    {
        return array_map(
            static fn (Style $s): StyleDto => StyleDto::depuis($s),
            $this->spectacles->stylesDisponibles(),
        );
    }

    public function dates(): array
    {
        return array_map(
            static fn (DateTimeImmutable $d): string => $d->format('Y-m-d'),
            $this->spectacles->datesDisponibles(),
        );
    }
}
