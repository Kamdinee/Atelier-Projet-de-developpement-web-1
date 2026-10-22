<?php

declare(strict_types=1);

namespace Nrv\Application\Port\Out;

use Nrv\Domain\Entity\Utilisateur;
use Nrv\Domain\Exception\EmailDejaUtiliseException;

// port de sortie : acces aux comptes, implemente par un adaptateur de persistance
interface UtilisateurRepositoryInterface
{
    public function trouverParEmail(string $email): ?Utilisateur;

    public function trouverParId(int $id): ?Utilisateur;

    // enregistre un nouveau compte et le renvoie avec son id
    /** @throws EmailDejaUtiliseException */
    public function ajouter(Utilisateur $utilisateur): Utilisateur;
}
