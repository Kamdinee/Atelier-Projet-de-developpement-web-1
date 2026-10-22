<?php

declare(strict_types=1);

namespace Nrv\Application\Dto;

use Nrv\Domain\Entity\Soiree;

// une soiree dans une liste (sans le detail des spectacles)
final class SoireeResumeDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $nom,
        public readonly string $thematique,
        public readonly string $date,
        public readonly string $heureDebut,
        public readonly string $lieu,
        public readonly int $tarifNormal,
        public readonly int $tarifReduit,
        public readonly int $placesRestantes,
        public readonly bool $complete,
    ) {
    }

    public static function depuis(Soiree $soiree): self
    {
        return new self(
            $soiree->id(),
            $soiree->nom(),
            $soiree->thematique(),
            $soiree->debut()->format('Y-m-d'),
            $soiree->debut()->format('H:i'),
            $soiree->lieu()->nom(),
            $soiree->tarifs()->normal(),
            $soiree->tarifs()->reduit(),
            $soiree->placesRestantes(),
            $soiree->estComplete(),
        );
    }
}
