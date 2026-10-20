<?php

declare(strict_types=1);

namespace Nrv\Application\Dto;

// une page de la liste des spectacles
final class PageSpectaclesDto
{
    /**
     * @param list<SpectacleDto> $spectacles
     */
    public function __construct(
        public readonly array $spectacles,
        public readonly int $total,
        public readonly int $page,
        public readonly int $taille,
        public readonly int $nbPages,
        public readonly FiltreSpectaclesDto $filtre,
    ) {
    }
}
