<?php

declare(strict_types=1);

namespace Nrv\Application\Port\Out;

// port de sortie : hachage des mots de passe (bcrypt dans l'infrastructure)
interface HacheurMotDePasseInterface
{
    public function hacher(string $motDePasse): string;

    public function verifier(string $motDePasse, string $hache): bool;
}
