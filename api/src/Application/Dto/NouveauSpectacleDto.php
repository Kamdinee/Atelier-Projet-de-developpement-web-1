<?php

declare(strict_types=1);

namespace Nrv\Application\Dto;

// spectacle saisi dans le backoffice (deja valide par l'adaptateur http)
final class NouveauSpectacleDto
{
    /**
     * @param list<string> $artistes
     */
    public function __construct(
        public readonly string $titre,
        public readonly string $description,
        public readonly string $style,
        public readonly array $artistes,
        public readonly string $horaire,
        public readonly ?string $video = null,
        public readonly ?string $image = null,
    ) {
    }
}
