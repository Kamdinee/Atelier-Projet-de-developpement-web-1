<?php

declare(strict_types=1);

namespace Nrv\Domain\Entity;

use Nrv\Domain\Exception\DonneeInvalideException;

// style de musique (classic rock, metal...)
final class Style
{
    public function __construct(
        private readonly int $id,
        private readonly string $libelle,
        private readonly string $slug,
    ) {
        if (trim($libelle) === '' || preg_match('/^[a-z0-9-]+$/', $slug) !== 1) {
            throw new DonneeInvalideException("style invalide : {$slug}");
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

    public function slug(): string
    {
        return $this->slug;
    }
}
