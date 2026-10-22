<?php

declare(strict_types=1);

namespace Nrv\Infrastructure\Persistence\Pdo;

use DateTimeImmutable;
use Nrv\Application\Port\Out\CommandeLectureRepositoryInterface;
use Nrv\Domain\ValueObject\CommandeResume;
use PDO;

// adaptateur pdo du port CommandeLectureRepositoryInterface
final class PDOCommandeLectureRepository implements CommandeLectureRepositoryInterface
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function commandesDe(int $utilisateurId): array
    {
        $requete = $this->pdo->prepare(
            'SELECT c.id, c.statut, c.total, c.cree_le, c.payee_le,
                (SELECT COUNT(*) FROM billets b WHERE b.commande_id = c.id) AS nb_billets
            FROM commandes c
            WHERE c.utilisateur_id = :utilisateur
            ORDER BY c.cree_le DESC, c.id'
        );
        $requete->bindValue('utilisateur', $utilisateurId, PDO::PARAM_INT);
        $requete->execute();

        return array_map(
            static fn (array $l): CommandeResume => new CommandeResume(
                (string) $l['id'],
                (string) $l['statut'],
                (int) $l['total'],
                (int) $l['nb_billets'],
                new DateTimeImmutable((string) $l['cree_le']),
                $l['payee_le'] !== null ? new DateTimeImmutable((string) $l['payee_le']) : null,
            ),
            $requete->fetchAll(PDO::FETCH_ASSOC),
        );
    }

    public function proprietaire(string $commandeId): ?int
    {
        $requete = $this->pdo->prepare('SELECT utilisateur_id FROM commandes WHERE id = :id');
        $requete->bindValue('id', $commandeId, PDO::PARAM_STR);
        $requete->execute();
        $id = $requete->fetchColumn();

        return $id === false ? null : (int) $id;
    }
}
