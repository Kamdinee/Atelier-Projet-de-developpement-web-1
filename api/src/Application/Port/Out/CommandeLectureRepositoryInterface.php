<?php

declare(strict_types=1);

namespace Nrv\Application\Port\Out;

use Nrv\Domain\ValueObject\CommandeResume;

// port de sortie : historique des commandes d'un client (lecture seule)
interface CommandeLectureRepositoryInterface
{
    /**
     * @return list<CommandeResume> les plus recentes d'abord
     */
    public function commandesDe(int $utilisateurId): array;

    // id du client proprietaire de la commande, null si elle n'existe pas
    public function proprietaire(string $commandeId): ?int;
}
