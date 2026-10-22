<?php

declare(strict_types=1);

namespace Nrv\Tests\Application\Mock;

use Nrv\Application\Port\Out\BilletRepositoryInterface;
use Nrv\Domain\Entity\Billet;

// faux depot de billets ; $commandes : commande => utilisateur
final class InMemoryBilletRepository implements BilletRepositoryInterface
{
    /**
     * @param list<Billet> $billets
     * @param array<string, int> $commandes
     */
    public function __construct(private readonly array $billets, private readonly array $commandes = [])
    {
    }

    public function trouverParJeton(string $jeton): ?Billet
    {
        foreach ($this->billets as $billet) {
            if ($billet->jeton()->valeur() === $jeton) {
                return $billet;
            }
        }

        return null;
    }

    public function billetsDeCommande(string $commandeId): array
    {
        return array_values(array_filter($this->billets, static fn (Billet $b) => $b->commandeId() === $commandeId));
    }

    public function billetsDeUtilisateur(int $utilisateurId): array
    {
        return array_values(array_filter(
            $this->billets,
            fn (Billet $b) => ($this->commandes[$b->commandeId()] ?? null) === $utilisateurId,
        ));
    }
}
