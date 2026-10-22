<?php

declare(strict_types=1);

namespace Nrv\Infrastructure\Persistence\Pdo;

use Nrv\Application\Port\Out\PassRepositoryInterface;
use PDO;

// Adaptateur PDO du port PassRepositoryInterface
final class PDOPassRepository implements PassRepositoryInterface
{
    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    public function lister(): array
    {
        $requete = $this->pdo->query(
            'SELECT id, libelle, nb_soirees, tarif_normal, tarif_reduit
             FROM pass
             ORDER BY id'
        );

        return array_map(
            static fn (array $ligne): array => [
                'id' => (int) $ligne['id'],
                'libelle' => (string) $ligne['libelle'],
                'nb_soirees' => (int) $ligne['nb_soirees'],
                'tarif_normal' => (int) $ligne['tarif_normal'],
                'tarif_reduit' => (int) $ligne['tarif_reduit'],
            ],
            $requete->fetchAll(PDO::FETCH_ASSOC),
        );
    }

    public function trouverParId(int $id): ?array
    {
        $requete = $this->pdo->prepare(
            'SELECT id, libelle, nb_soirees, tarif_normal, tarif_reduit
             FROM pass
             WHERE id = :id'
        );
        $requete->execute(['id' => $id]);
        $ligne = $requete->fetch(PDO::FETCH_ASSOC);

        if (!$ligne) {
            return null;
        }

        return [
            'id' => (int) $ligne['id'],
            'libelle' => (string) $ligne['libelle'],
            'nb_soirees' => (int) $ligne['nb_soirees'],
            'tarif_normal' => (int) $ligne['tarif_normal'],
            'tarif_reduit' => (int) $ligne['tarif_reduit'],
        ];
    }
}
