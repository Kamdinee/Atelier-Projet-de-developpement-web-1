<?php

declare(strict_types=1);

namespace Nrv\Application\Dto;

use Nrv\Domain\Entity\Soiree;
use Nrv\Domain\Entity\Spectacle;

// detail d'une soiree envoye a l'adaptateur http
final class SoireeDto
{
    /**
     * @param array{id: int, nom: string, adresse: string} $lieu
     * @param list<SpectacleDto> $spectacles
     */
    public function __construct(
        public readonly int $id,
        public readonly string $nom,
        public readonly string $thematique,
        public readonly string $date,
        public readonly string $heureDebut,
        public readonly array $lieu,
        public readonly int $tarifNormal,
        public readonly int $tarifReduit,
        public readonly int $jauge,
        public readonly int $placesRestantes,
        public readonly bool $complete,
        public readonly array $spectacles,
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
            [
                'id' => $soiree->lieu()->id(),
                'nom' => $soiree->lieu()->nom(),
                'adresse' => $soiree->lieu()->adresse(),
            ],
            $soiree->tarifs()->normal(),
            $soiree->tarifs()->reduit(),
            $soiree->jauge(),
            $soiree->placesRestantes(),
            $soiree->estComplete(),
            array_map(static fn (Spectacle $s): SpectacleDto => SpectacleDto::depuis($s), $soiree->spectacles()),
        );
    }
}
