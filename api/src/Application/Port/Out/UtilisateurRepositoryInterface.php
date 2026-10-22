<?php

declare(strict_types=1);

namespace Nrv\Application\Port\Out;

interface UtilisateurRepositoryInterface
{
    public function trouverParEmail(string $email): ?array;

    public function creer(
        string $email,
        string $nom,
        string $prenom,
        string $motDePasseHache,
    ): int;
}
