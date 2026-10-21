<?php

declare(strict_types=1);

namespace Nrv\Infrastructure\Http\Validation;

use Nrv\Infrastructure\Http\Exception\RequeteInvalideException;

// verifie un identifiant passe dans l'url (/soirees/{id})
final class IdentifiantValidator
{
    public function entierPositif(mixed $valeur, string $nom = 'id'): int
    {
        // 9 chiffres au plus : reste dans les limites d'un int
        if (!is_string($valeur) || !ctype_digit($valeur) || strlen($valeur) > 9 || (int) $valeur < 1) {
            throw new RequeteInvalideException([$nom => 'entier positif attendu']);
        }

        return (int) $valeur;
    }
}
