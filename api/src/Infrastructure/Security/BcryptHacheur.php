<?php

declare(strict_types=1);

namespace Nrv\Infrastructure\Security;

use Nrv\Application\Port\Out\HacheurMotDePasseInterface;

// adaptateur du port HacheurMotDePasseInterface : bcrypt (sel aleatoire inclus dans le hache)
final class BcryptHacheur implements HacheurMotDePasseInterface
{
    // meme cout que le jeu de donnees (bin/seed.php)
    private const COUT = 12;

    public function hacher(string $motDePasse): string
    {
        return password_hash($motDePasse, PASSWORD_BCRYPT, ['cost' => self::COUT]);
    }

    public function verifier(string $motDePasse, string $hache): bool
    {
        return password_verify($motDePasse, $hache);
    }
}
