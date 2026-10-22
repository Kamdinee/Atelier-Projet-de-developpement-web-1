<?php

declare(strict_types=1);

namespace Nrv\Application\Dto;

// tableau de bord du backoffice : synthese du festival + une ligne par soiree
final class JaugesDto
{
    /**
     * @param list<JaugeSoireeDto> $soirees
     */
    public function __construct(
        public readonly int $jaugeTotale,
        public readonly int $placesVendues,
        public readonly float $tauxRemplissage,
        public readonly int $recette,
        public readonly int $soireesCompletes,
        public readonly int $soireesPresqueCompletes,
        public readonly array $soirees,
    ) {
    }
}
