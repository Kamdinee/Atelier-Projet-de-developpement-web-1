<?php

declare(strict_types=1);

namespace Nrv\Tests\Application\Mock;

use Nrv\Application\Port\Out\CommandeRepositoryInterface;

final class InMemoryCommandeRepository implements CommandeRepositoryInterface
{
    /** @var array<string, array<string, mixed>> */
    public array $commandes = [];

    public function creer(array $commande, array $lignes): void
    {
        $this->commandes[$commande['id']] = [
            'id' => $commande['id'],
            'utilisateur_id' => $commande['utilisateur_id'],
            'statut' => $commande['statut'],
            'total' => $commande['total'],
            'carte_fin' => null,
            'cree_le' => date('Y-m-d H:i:s'),
            'payee_le' => null,
            'lignes' => $lignes,
            'billets' => [],
        ];
    }

    public function trouverParId(string $id): ?array
    {
        return $this->commandes[$id] ?? null;
    }

    public function enregistrerPaiement(
        string $commandeId,
        string $carteFin,
        array $billets,
        array $jaugesAIncrementer,
    ): void {
        if (!isset($this->commandes[$commandeId])) {
            return;
        }

        $this->commandes[$commandeId]['statut'] = 'PAYEE';
        $this->commandes[$commandeId]['payee_le'] = date('Y-m-d H:i:s');
        $this->commandes[$commandeId]['carte_fin'] = $carteFin;
        $this->commandes[$commandeId]['billets'] = $billets;
    }
}
