<?php

declare(strict_types=1);

namespace Nrv\Application\Port\Out;

use Nrv\Domain\Entity\Billet;

// port de sortie : lecture des billets emis
interface BilletRepositoryInterface
{
    public function trouverParJeton(string $jeton): ?Billet;

    /**
     * billets d'une commande, par reference
     *
     * @return list<Billet>
     */
    public function billetsDeCommande(string $commandeId): array;

    /**
     * billets de toutes les commandes payees d'un utilisateur, les plus recents d'abord
     *
     * @return list<Billet>
     */
    public function billetsDeUtilisateur(int $utilisateurId): array;
}
