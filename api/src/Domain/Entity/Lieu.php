<?php

declare(strict_types=1);

namespace Nrv\Domain\Entity;

use Nrv\Domain\Exception\DonneeInvalideException;

final class Lieu
{
    public function __construct(
        private readonly int $id,
        private readonly string $nom,
        private readonly string $adresse,
        private readonly int $capacite,
    ) {
        if ($capacite < 1) {
            throw new DonneeInvalideException('la capacite d\'un lieu doit etre positive');
        }
    }

    public function id(): int
    {
        return $this->id;
    }

    public function nom(): string
    {
        return $this->nom;
    }

    public function adresse(): string
    {
        return $this->adresse;
    }

    public function capacite(): int
    {
        return $this->capacite;
    }
}
