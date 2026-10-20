<?php

declare(strict_types=1);

namespace Nrv\Infrastructure\Http\Presenter;

use Nrv\Application\Dto\SpectacleDto;

// format json complet d'un spectacle (detail soiree et GET /spectacles/{id})
final class SpectacleJson
{
    public static function complet(SpectacleDto $s): array
    {
        return [
            'id' => $s->id,
            'titre' => $s->titre,
            'artistes' => $s->artistes,
            'description' => $s->description,
            'style' => ['slug' => $s->style->slug, 'libelle' => $s->style->libelle],
            'date' => $s->date,
            'horaire' => $s->horaire,
            'images' => $s->images,
            'video' => $s->video,
        ];
    }
}
