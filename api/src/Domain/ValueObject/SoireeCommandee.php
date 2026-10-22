<?php

declare(strict_types=1);

namespace Nrv\Domain\ValueObject;

use DateTimeImmutable;

// soiree d'une ligne de commande : ce qu'il faut pour l'afficher dans le recapitulatif
final class SoireeCommandee
{
    public function __construct(
        public readonly int $id,
        public readonly string $nom,
        public readonly DateTimeImmutable $debut,
    ) {
    }
}
