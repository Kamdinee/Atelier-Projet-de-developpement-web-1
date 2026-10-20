<?php

declare(strict_types=1);

namespace Nrv\Domain\ValueObject;

use Nrv\Domain\Exception\TarifInvalideException;

// tarif normal et reduit en centimes
final class Tarifs
{
    public function __construct(
        private readonly int $normal,
        private readonly int $reduit,
    ) {
        if ($normal < 0 || $reduit < 0) {
            throw new TarifInvalideException('un tarif ne peut pas etre negatif');
        }
        if ($reduit > $normal) {
            throw new TarifInvalideException('le tarif reduit ne peut pas depasser le tarif normal');
        }
    }

    public function normal(): int
    {
        return $this->normal;
    }

    public function reduit(): int
    {
        return $this->reduit;
    }

    public function pour(CategorieTarif $categorie): int
    {
        return match ($categorie) {
            CategorieTarif::Normal => $this->normal,
            CategorieTarif::Reduit => $this->reduit,
        };
    }
}
