<?php

declare(strict_types=1);

namespace Nrv\Infrastructure\Http\Validation;

use Nrv\Infrastructure\Http\Exception\RequeteInvalideException;

// verifie un identifiant passe dans l'url (/soirees/{id})
final class IdentifiantValidator
{
    public function entierPositif(mixed $valeur, string $nom = 'id'): int
    {
        if (!is_string($valeur) || !ctype_digit($valeur) || (int) $valeur < 1) {
            throw new RequeteInvalideException([$nom => 'entier positif attendu']);
        }

        return (int) $valeur;
    }
}
