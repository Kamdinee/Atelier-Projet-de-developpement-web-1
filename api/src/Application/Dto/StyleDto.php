<?php

declare(strict_types=1);

namespace Nrv\Application\Dto;

use Nrv\Domain\Entity\Style;

final class StyleDto
{
    public function __construct(
        public readonly string $slug,
        public readonly string $libelle,
    ) {
    }

    public static function depuis(Style $style): self
    {
        return new self($style->slug(), $style->libelle());
    }
}
