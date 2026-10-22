<?php

declare(strict_types=1);

namespace Nrv\Domain\ValueObject;

use DateTimeImmutable;
use Nrv\Domain\Exception\DonneeInvalideException;

// remplissage et recette d'une soiree, pour le backoffice
final class JaugeSoiree
{
    // a partir de ce taux une soiree est signalee presque complete
    public const SEUIL_PRESQUE_COMPLETE = 90.0;

    public function __construct(
        private readonly int $soireeId,
        private readonly string $nom,
        private readonly DateTimeImmutable $debut,
        private readonly string $lieu,
        private readonly int $jauge,
        private readonly int $placesVendues,
        private readonly int $billetsSoiree,
        private readonly int $billetsPass,
        private readonly int $recette,
    ) {
        if ($jauge < 1) {
            throw new DonneeInvalideException('la jauge doit etre positive');
        }
        if ($placesVendues < 0 || $placesVendues > $jauge) {
            throw new DonneeInvalideException('le nombre de places vendues doit etre entre 0 et la jauge');
        }
        if ($billetsSoiree < 0 || $billetsPass < 0 || $recette < 0) {
            throw new DonneeInvalideException('les ventes ne peuvent pas etre negatives');
        }
    }

    public function soireeId(): int
    {
        return $this->soireeId;
    }

    public function nom(): string
    {
        return $this->nom;
    }

    public function debut(): DateTimeImmutable
    {
        return $this->debut;
    }

    public function lieu(): string
    {
        return $this->lieu;
    }

    public function jauge(): int
    {
        return $this->jauge;
    }

    public function placesVendues(): int
    {
        return $this->placesVendues;
    }

    public function placesRestantes(): int
    {
        return $this->jauge - $this->placesVendues;
    }

    public function billetsSoiree(): int
    {
        return $this->billetsSoiree;
    }

    public function billetsPass(): int
    {
        return $this->billetsPass;
    }

    // recette en centimes (un pass compte pour un tiers dans chacune de ses soirees)
    public function recette(): int
    {
        return $this->recette;
    }

    // pourcentage a une decimale
    public function tauxRemplissage(): float
    {
        return round(100 * $this->placesVendues / $this->jauge, 1);
    }

    public function estComplete(): bool
    {
        return $this->placesRestantes() === 0;
    }

    public function estPresqueComplete(): bool
    {
        return !$this->estComplete() && $this->tauxRemplissage() >= self::SEUIL_PRESQUE_COMPLETE;
    }
}
