<?php

declare(strict_types=1);

namespace Nrv\Application\Port\Out;

use Nrv\Domain\Entity\Commande;
use Nrv\Domain\ValueObject\NouveauBillet;

// port de sortie : enregistrement des commandes et emission des billets
interface CommandeRepositoryInterface
{
    public function ajouter(Commande $commande): void;

    public function trouverParId(string $id): ?Commande;

    // meme lecture, en verrouillant la commande jusqu'a la fin de la transaction (pas de double paiement)
    public function trouverPourPaiement(string $id): ?Commande;

    /**
     * statut payee, fin de la carte, et billets emis
     *
     * @param list<NouveauBillet> $billets
     */
    public function enregistrerPaiement(Commande $commande, array $billets): void;
}
