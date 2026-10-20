<?php

declare(strict_types=1);

namespace Nrv\Domain\Repository;

use DateTimeImmutable;
use Nrv\Domain\Entity\Spectacle;
use Nrv\Domain\Entity\Style;
use Nrv\Domain\ValueObject\CriteresSpectacles;
use Nrv\Domain\ValueObject\ResultatPagine;

// port de sortie : acces aux spectacles
interface SpectacleRepositoryInterface
{
    // liste paginee, filtree par date et/ou style si renseignes
    public function rechercher(CriteresSpectacles $criteres): ResultatPagine;

    public function trouverParId(int $id): ?Spectacle;

    /**
     * styles qui ont au moins un spectacle au programme
     *
     * @return list<Style>
     */
    public function stylesDisponibles(): array;

    /**
     * dates des soirees du festival
     *
     * @return list<DateTimeImmutable>
     */
    public function datesDisponibles(): array;
}
