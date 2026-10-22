<?php

declare(strict_types=1);

namespace Nrv\Domain\ValueObject;

// un billet donne acces a une soiree, ou a 3 soirees pour un pass
enum TypeBillet: string
{
    case Soiree = 'SOIREE';
    case Pass = 'PASS';

    // nombre de soirees couvertes par un billet de ce type
    public function nombreDeSoirees(): int
    {
        return match ($this) {
            self::Soiree => 1,
            self::Pass => 3,
        };
    }
}
