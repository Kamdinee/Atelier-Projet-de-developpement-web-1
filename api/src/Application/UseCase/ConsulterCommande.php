<?php

declare(strict_types=1);

namespace Nrv\Application\UseCase;

use Nrv\Application\Port\Out\CommandeRepositoryInterface;
use RuntimeException;

final class ConsulterCommande
{
    public function __construct(
        private readonly CommandeRepositoryInterface $commandeRepository,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function executer(int $utilisateurId, string $commandeId, string $role = 'CLIENT'): array
    {
        $commande = $this->commandeRepository->trouverParId($commandeId);
        if ($commande === null) {
            throw new RuntimeException("La commande {$commandeId} n'existe pas.");
        }

        if ($commande['utilisateur_id'] !== $utilisateurId && strtoupper($role) !== 'ADMIN') {
            throw new RuntimeException("Vous n'êtes pas autorisé à consulter cette commande.");
        }

        return $commande;
    }
}
