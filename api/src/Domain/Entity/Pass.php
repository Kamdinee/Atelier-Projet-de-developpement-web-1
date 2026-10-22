<?php

declare(strict_types=1);

namespace Nrv\Domain\Entity;

use Nrv\Domain\Exception\DonneeInvalideException;
use Nrv\Domain\ValueObject\Tarifs;

// formule Pass : un billet specifique valable sur n soirees distinctes (3 pour le Pass 3 jours)
final class Pass
{
    public function __construct(
        private readonly int $id,
        private readonly string $libelle,
        private readonly int $nbSoirees,
        private readonly Tarifs $tarifs,
    ) {
        if ($nbSoirees < 2) {
            throw new DonneeInvalideException('un pass donne acces a au moins 2 soirees');
        }
    }

    public function id(): int
    {
        return $this->id;
    }

    public function libelle(): string
    {
        return $this->libelle;
    }

    public function nbSoirees(): int
    {
        return $this->nbSoirees;
    }

    public function tarifs(): Tarifs
    {
        return $this->tarifs;
    }

    // les soirees choisies doivent etre distinctes et au bon nombre
    /**
     * @param list<int> $soireeIds
     */
    public function verifierSoirees(array $soireeIds): void
    {
        if (count($soireeIds) !== $this->nbSoirees || count(array_unique($soireeIds)) !== $this->nbSoirees) {
            throw new DonneeInvalideException("le {$this->libelle} porte sur {$this->nbSoirees} soirees distinctes");
        }
    }
}
