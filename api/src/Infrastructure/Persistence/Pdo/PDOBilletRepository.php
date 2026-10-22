<?php

declare(strict_types=1);

namespace Nrv\Infrastructure\Persistence\Pdo;

use DateTimeImmutable;
use Nrv\Application\Port\Out\BilletRepositoryInterface;
use Nrv\Domain\Entity\Billet;
use Nrv\Domain\ValueObject\CategorieTarif;
use Nrv\Domain\ValueObject\JetonBillet;
use Nrv\Domain\ValueObject\SoireeBillet;
use Nrv\Domain\ValueObject\TypeBillet;
use PDO;

// adaptateur pdo du port BilletRepositoryInterface
// les soirees d'un pass sont dans billet_pass_soirees, celle d'un billet soiree dans billets.soiree_id
final class PDOBilletRepository implements BilletRepositoryInterface
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function trouverParJeton(string $jeton): ?Billet
    {
        $billets = $this->charger('WHERE b.token = ?', [$jeton]);

        return $billets[0] ?? null;
    }

    public function billetsDeCommande(string $commandeId): array
    {
        return $this->charger('WHERE b.commande_id = ? ORDER BY b.reference', [$commandeId]);
    }

    public function billetsDeUtilisateur(int $utilisateurId): array
    {
        return $this->charger(
            "JOIN commandes c ON c.id = b.commande_id
            WHERE c.utilisateur_id = ? AND c.statut = 'PAYEE'
            ORDER BY b.emis_le DESC, b.reference",
            [$utilisateurId],
        );
    }

    /**
     * @param list<string|int> $parametres
     * @return list<Billet>
     */
    private function charger(string $suite, array $parametres): array
    {
        $requete = $this->pdo->prepare(
            'SELECT b.id, b.token, b.reference, b.commande_id, b.type, b.categorie, b.prix_paye,
                b.nom_acheteur, b.emis_le, b.soiree_id
            FROM billets b ' . $suite
        );
        foreach ($parametres as $i => $valeur) {
            $requete->bindValue($i + 1, $valeur, is_int($valeur) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $requete->execute();
        $lignes = $requete->fetchAll(PDO::FETCH_ASSOC);
        if ($lignes === []) {
            return [];
        }

        // soirees de chaque billet : 1 pour un billet soiree, 3 pour un pass
        $soireesParBillet = [];
        $pass = [];
        foreach ($lignes as $l) {
            if ($l['type'] === TypeBillet::Pass->value) {
                $pass[] = (int) $l['id'];
            } else {
                $soireesParBillet[(int) $l['id']] = [(int) $l['soiree_id']];
            }
        }
        foreach ($this->soireesDesPass($pass) as $billetId => $ids) {
            $soireesParBillet[$billetId] = $ids;
        }

        $soirees = $this->soirees(array_values(array_unique(array_merge(...array_values($soireesParBillet)))));

        return array_map(
            static fn (array $l): Billet => new Billet(
                (int) $l['id'],
                (string) $l['reference'],
                new JetonBillet((string) $l['token']),
                (string) $l['commande_id'],
                TypeBillet::from((string) $l['type']),
                CategorieTarif::from((string) $l['categorie']),
                (int) $l['prix_paye'],
                (string) $l['nom_acheteur'],
                new DateTimeImmutable((string) $l['emis_le']),
                array_map(static fn (int $id): SoireeBillet => $soirees[$id], $soireesParBillet[(int) $l['id']] ?? []),
            ),
            $lignes,
        );
    }

    /**
     * @param list<int> $billetIds
     * @return array<int, list<int>> billet => soirees
     */
    private function soireesDesPass(array $billetIds): array
    {
        if ($billetIds === []) {
            return [];
        }
        $requete = $this->pdo->prepare(
            'SELECT billet_id, soiree_id FROM billet_pass_soirees
            WHERE billet_id IN (' . $this->marqueurs($billetIds) . ')'
        );
        $this->lier($requete, $billetIds);
        $requete->execute();

        $resultat = [];
        foreach ($requete->fetchAll(PDO::FETCH_ASSOC) as $l) {
            $resultat[(int) $l['billet_id']][] = (int) $l['soiree_id'];
        }

        return $resultat;
    }

    /**
     * @param list<int> $ids
     * @return array<int, SoireeBillet>
     */
    private function soirees(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $spectacles = [];
        $requete = $this->pdo->prepare(
            'SELECT soiree_id, titre, horaire_previsionnel FROM spectacles
            WHERE soiree_id IN (' . $this->marqueurs($ids) . ')
            ORDER BY soiree_id, horaire_previsionnel'
        );
        $this->lier($requete, $ids);
        $requete->execute();
        foreach ($requete->fetchAll(PDO::FETCH_ASSOC) as $l) {
            $spectacles[(int) $l['soiree_id']][] = [
                'titre' => (string) $l['titre'],
                'horaire' => substr((string) $l['horaire_previsionnel'], 0, 5),
            ];
        }

        $requete = $this->pdo->prepare(
            'SELECT s.id, s.nom, s.date_soiree, s.heure_debut, l.nom AS lieu, l.adresse
            FROM soirees s
            JOIN lieux l ON l.id = s.lieu_id
            WHERE s.id IN (' . $this->marqueurs($ids) . ')'
        );
        $this->lier($requete, $ids);
        $requete->execute();

        $resultat = [];
        foreach ($requete->fetchAll(PDO::FETCH_ASSOC) as $l) {
            $id = (int) $l['id'];
            $resultat[$id] = new SoireeBillet(
                $id,
                (string) $l['nom'],
                new DateTimeImmutable($l['date_soiree'] . ' ' . $l['heure_debut']),
                (string) $l['lieu'],
                (string) $l['adresse'],
                $spectacles[$id] ?? [],
            );
        }

        return $resultat;
    }

    /**
     * @param list<int> $valeurs
     */
    private function marqueurs(array $valeurs): string
    {
        return implode(', ', array_fill(0, count($valeurs), '?'));
    }

    /**
     * @param list<int> $valeurs
     */
    private function lier(\PDOStatement $requete, array $valeurs): void
    {
        foreach ($valeurs as $i => $valeur) {
            $requete->bindValue($i + 1, $valeur, PDO::PARAM_INT);
        }
    }
}
