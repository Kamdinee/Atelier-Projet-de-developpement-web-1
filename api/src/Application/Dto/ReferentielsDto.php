<?php

declare(strict_types=1);

namespace Nrv\Application\Dto;

// listes proposees dans les formulaires du backoffice
final class ReferentielsDto
{
    /**
     * @param list<array{id: int, nom: string, adresse: string, capacite: int}> $lieux
     * @param list<StyleDto> $styles
     */
    public function __construct(
        public readonly array $lieux,
        public readonly array $styles,
    ) {
    }
}
