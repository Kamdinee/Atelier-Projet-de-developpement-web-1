<?php

declare(strict_types=1);

namespace Nrv\Application\Dto;

use Nrv\Domain\Entity\Pass;

final class PassDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $libelle,
        public readonly int $nbSoirees,
        public readonly int $tarifNormal,
        public readonly int $tarifReduit,
    ) {
    }

    public static function depuis(Pass $pass): self
    {
        return new self(
            $pass->id(),
            $pass->libelle(),
            $pass->nbSoirees(),
            $pass->tarifs()->normal(),
            $pass->tarifs()->reduit(),
        );
    }
}
