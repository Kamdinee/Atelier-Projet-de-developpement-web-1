<?php

declare(strict_types=1);

namespace Nrv\Application\Dto;

use Nrv\Domain\ValueObject\JaugeSoiree;

final class JaugeSoireeDto
{
    public function __construct(
        public readonly int $soireeId,
        public readonly string $nom,
        public readonly string $date,
        public readonly string $heureDebut,
        public readonly string $lieu,
        public readonly int $jauge,
        public readonly int $placesVendues,
        public readonly int $placesRestantes,
        public readonly float $tauxRemplissage,
        public readonly int $billetsSoiree,
        public readonly int $billetsPass,
        public readonly int $recette,
        public readonly bool $complete,
        public readonly bool $presqueComplete,
    ) {
    }

    public static function depuis(JaugeSoiree $j): self
    {
        return new self(
            $j->soireeId(),
            $j->nom(),
            $j->debut()->format('Y-m-d'),
            $j->debut()->format('H:i'),
            $j->lieu(),
            $j->jauge(),
            $j->placesVendues(),
            $j->placesRestantes(),
            $j->tauxRemplissage(),
            $j->billetsSoiree(),
            $j->billetsPass(),
            $j->recette(),
            $j->estComplete(),
            $j->estPresqueComplete(),
        );
    }
}
