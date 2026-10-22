<?php

declare(strict_types=1);

namespace Nrv\Tests\Application\Mock;

use Nrv\Application\Port\Out\CommandeRepositoryInterface;
use Nrv\Domain\Entity\Commande;
use Nrv\Domain\ValueObject\NouveauBillet;

final class InMemoryCommandeRepository implements CommandeRepositoryInterface
{
    /** @var array<string, Commande> */
    public array $commandes = [];

    /** @var list<NouveauBillet> billets emis au paiement */
    public array $billets = [];

    public function ajouter(Commande $commande): void
    {
        $this->commandes[$commande->id()] = $commande;
    }

    public function trouverParId(string $id): ?Commande
    {
        // copie : comme une relecture en base, les modifications non enregistrees ne restent pas
        return isset($this->commandes[$id]) ? clone $this->commandes[$id] : null;
    }

    public function trouverPourPaiement(string $id): ?Commande
    {
        return $this->trouverParId($id);
    }

    public function enregistrerPaiement(Commande $commande, array $billets): void
    {
        $this->commandes[$commande->id()] = $commande;
        array_push($this->billets, ...$billets);
    }
}
