<?php

declare(strict_types=1);

namespace Nrv\Application\Dto;

// filtres recus de la requete http (deja valides par l'adaptateur)
final class FiltreSpectaclesDto
{
    public function __construct(
        public readonly ?string $date = null,
        public readonly ?string $style = null,
        public readonly int $page = 1,
        public readonly int $taille = 12,
    ) {
    }
}
