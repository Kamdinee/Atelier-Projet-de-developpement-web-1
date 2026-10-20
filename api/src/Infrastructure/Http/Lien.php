<?php

declare(strict_types=1);

namespace Nrv\Infrastructure\Http;

// liens hateoas de l'api
final class Lien
{
    public static function spectacle(int $id): array
    {
        return ['href' => "/spectacles/{$id}"];
    }

    public static function soiree(int $id): array
    {
        return ['href' => "/soirees/{$id}"];
    }

    // lien vers la liste en gardant les filtres
    public static function spectacles(array $parametres): array
    {
        $parametres = array_filter($parametres, static fn ($v) => $v !== null && $v !== '');
        $query = http_build_query($parametres);

        return ['href' => '/spectacles' . ($query !== '' ? '?' . $query : '')];
    }
}
