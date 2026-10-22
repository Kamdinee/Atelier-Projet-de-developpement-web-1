<?php

declare(strict_types=1);

namespace Nrv\Tests\Application\Mock;

use Nrv\Application\Port\Out\HacheurMotDePasseInterface;

// hachage previsible pour les tests (le vrai bcrypt est teste a part)
final class FauxHacheur implements HacheurMotDePasseInterface
{
    public function hacher(string $motDePasse): string
    {
        return 'hache:' . $motDePasse;
    }

    public function verifier(string $motDePasse, string $hache): bool
    {
        return $hache === 'hache:' . $motDePasse;
    }
}
