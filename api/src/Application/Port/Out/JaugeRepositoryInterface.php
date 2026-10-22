<?php

declare(strict_types=1);

namespace Nrv\Application\Port\Out;

use Nrv\Domain\ValueObject\JaugeSoiree;

// port de sortie : remplissage et recette des soirees (vue v_jauges_soirees)
interface JaugeRepositoryInterface
{
    /**
     * @return list<JaugeSoiree> par date
     */
    public function toutes(): array;
}
