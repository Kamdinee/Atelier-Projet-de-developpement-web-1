<?php

declare(strict_types=1);

namespace Nrv\Tests\Application\Mock;

use Nrv\Application\Port\Out\CommandeLectureRepositoryInterface;
use Nrv\Domain\ValueObject\CommandeResume;

final class InMemoryCommandeLectureRepository implements CommandeLectureRepositoryInterface
{
    /**
     * @param array<int, list<CommandeResume>> $parUtilisateur
     */
    public function __construct(private readonly array $parUtilisateur)
    {
    }

    public function commandesDe(int $utilisateurId): array
    {
        return $this->parUtilisateur[$utilisateurId] ?? [];
    }

    public function proprietaire(string $commandeId): ?int
    {
        foreach ($this->parUtilisateur as $utilisateur => $commandes) {
            foreach ($commandes as $commande) {
                if ($commande->id() === $commandeId) {
                    return $utilisateur;
                }
            }
        }

        return null;
    }
}
