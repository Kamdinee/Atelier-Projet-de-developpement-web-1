<?php

declare(strict_types=1);

namespace Nrv\Application\Dto;

// soiree saisie dans le backoffice avec ses 2 a 3 spectacles
final class NouvelleSoireeDto
{
    /**
     * @param list<NouveauSpectacleDto> $spectacles
     */
    public function __construct(
        public readonly string $nom,
        public readonly string $thematique,
        public readonly string $date,
        public readonly string $heureDebut,
        public readonly int $lieuId,
        public readonly ?int $jauge,
        public readonly int $tarifNormal,
        public readonly int $tarifReduit,
        public readonly array $spectacles,
    ) {
    }
}
