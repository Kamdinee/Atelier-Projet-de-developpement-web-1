<?php

declare(strict_types=1);

namespace Nrv\Application\Dto;

// carte saisie par le client, deja controlee en format par l'adaptateur http
// (le cas d'usage en fait une CarteBancaire du domaine : luhn, expiration)
final class CarteDto
{
    public function __construct(
        public readonly string $numero,
        public readonly int $moisExpiration,
        public readonly int $anneeExpiration,
        public readonly string $cryptogramme,
    ) {
    }
}
