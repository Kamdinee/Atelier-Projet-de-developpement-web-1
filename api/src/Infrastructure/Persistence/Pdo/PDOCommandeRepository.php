<?php

declare(strict_types=1);

namespace Nrv\Infrastructure\Persistence\Pdo;

use Nrv\Application\Port\Out\CommandeRepositoryInterface;
use PDO;
use Throwable;

final class PDOCommandeRepository implements CommandeRepositoryInterface
{
    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    public function creer(array $commande, array $lignes): void
    {
        $this->pdo->beginTransaction();

        try {
            $stmtCommande = $this->pdo->prepare(
                'INSERT INTO commandes (id, utilisateur_id, statut, total, cree_le)
                 VALUES (:id, :uid, :statut, :total, NOW())'
            );
            $stmtCommande->execute([
                'id' => $commande['id'],
                'uid' => $commande['utilisateur_id'],
                'statut' => $commande['statut'],
                'total' => $commande['total'],
            ]);

            $stmtLigne = $this->pdo->prepare(
                'INSERT INTO lignes_commande (commande_id, type, soiree_id, pass_id, categorie, quantite, prix_unitaire)
                 VALUES (:cid, :type, :sid, :pid, :cat, :qte, :prix)'
            );

            $stmtPassSoiree = $this->pdo->prepare(
                'INSERT INTO ligne_pass_soirees (ligne_id, soiree_id)
                 VALUES (:lid, :sid)'
            );

            foreach ($lignes as $ligne) {
                $stmtLigne->execute([
                    'cid' => $commande['id'],
                    'type' => $ligne['type'],
                    'sid' => $ligne['soiree_id'],
                    'pid' => $ligne['pass_id'],
                    'cat' => $ligne['categorie'],
                    'qte' => $ligne['quantite'],
                    'prix' => $ligne['prix_unitaire'],
                ]);

                $ligneId = (int) $this->pdo->lastInsertId();

                if ($ligne['type'] === 'PASS' && !empty($ligne['soiree_ids'])) {
                    foreach ($ligne['soiree_ids'] as $soireeId) {
                        $stmtPassSoiree->execute([
                            'lid' => $ligneId,
                            'sid' => $soireeId,
                        ]);
                    }
                }
            }

            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function trouverParId(string $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT c.id, c.utilisateur_id, c.statut, c.total, c.carte_fin, c.cree_le, c.payee_le,
                    u.email AS utilisateur_email, u.nom AS utilisateur_nom, u.prenom AS utilisateur_prenom
             FROM commandes c
             JOIN utilisateurs u ON u.id = c.utilisateur_id
             WHERE c.id = :id'
        );
        $stmt->execute(['id' => $id]);
        $commande = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$commande) {
            return null;
        }

        $stmtLignes = $this->pdo->prepare(
            'SELECT lc.id, lc.type, lc.soiree_id, lc.pass_id, lc.categorie, lc.quantite, lc.prix_unitaire,
                    s.nom AS soiree_nom, s.date_soiree, s.heure_debut,
                    p.libelle AS pass_libelle
             FROM lignes_commande lc
             LEFT JOIN soirees s ON s.id = lc.soiree_id
             LEFT JOIN pass p ON p.id = lc.pass_id
             WHERE lc.commande_id = :id
             ORDER BY lc.id'
        );
        $stmtLignes->execute(['id' => $id]);
        $lignesRaw = $stmtLignes->fetchAll(PDO::FETCH_ASSOC);

        $stmtPassSoirees = $this->pdo->prepare(
            'SELECT lps.soiree_id, s.nom AS soiree_nom, s.date_soiree
             FROM ligne_pass_soirees lps
             JOIN soirees s ON s.id = lps.soiree_id
             WHERE lps.ligne_id = :lid
             ORDER BY s.date_soiree'
        );

        $lignes = [];
        foreach ($lignesRaw as $l) {
            $ligneData = [
                'id' => (int) $l['id'],
                'type' => (string) $l['type'],
                'soiree_id' => $l['soiree_id'] !== null ? (int) $l['soiree_id'] : null,
                'soiree_nom' => $l['soiree_nom'],
                'date_soiree' => $l['date_soiree'],
                'heure_debut' => $l['heure_debut'],
                'pass_id' => $l['pass_id'] !== null ? (int) $l['pass_id'] : null,
                'pass_libelle' => $l['pass_libelle'],
                'categorie' => (string) $l['categorie'],
                'quantite' => (int) $l['quantite'],
                'prix_unitaire' => (int) $l['prix_unitaire'],
                'montant' => (int) $l['quantite'] * (int) $l['prix_unitaire'],
            ];

            if ($l['type'] === 'PASS') {
                $stmtPassSoirees->execute(['lid' => (int) $l['id']]);
                $ligneData['soirees'] = $stmtPassSoirees->fetchAll(PDO::FETCH_ASSOC);
            }

            $lignes[] = $ligneData;
        }

        $commande['total'] = (int) $commande['total'];
        $commande['utilisateur_id'] = (int) $commande['utilisateur_id'];
        $commande['lignes'] = $lignes;

        // Billets émis si la commande est payée
        if ($commande['statut'] === 'PAYEE') {
            $stmtBillets = $this->pdo->prepare(
                'SELECT b.id, b.token, b.reference, b.type, b.soiree_id, b.pass_id,
                        b.categorie, b.prix_paye, b.nom_acheteur, b.emis_le,
                        s.nom AS soiree_nom, s.date_soiree, s.heure_debut,
                        p.libelle AS pass_libelle
                 FROM billets b
                 LEFT JOIN soirees s ON s.id = b.soiree_id
                 LEFT JOIN pass p ON p.id = b.pass_id
                 WHERE b.commande_id = :id
                 ORDER BY b.id'
            );
            $stmtBillets->execute(['id' => $id]);
            $billetsRaw = $stmtBillets->fetchAll(PDO::FETCH_ASSOC);

            $stmtBilletPass = $this->pdo->prepare(
                'SELECT bps.soiree_id, s.nom AS soiree_nom, s.date_soiree
                 FROM billet_pass_soirees bps
                 JOIN soirees s ON s.id = bps.soiree_id
                 WHERE bps.billet_id = :bid
                 ORDER BY s.date_soiree'
            );

            $billets = [];
            foreach ($billetsRaw as $b) {
                $bData = [
                    'id' => (int) $b['id'],
                    'token' => (string) $b['token'],
                    'reference' => (string) $b['reference'],
                    'type' => (string) $b['type'],
                    'soiree_id' => $b['soiree_id'] !== null ? (int) $b['soiree_id'] : null,
                    'soiree_nom' => $b['soiree_nom'],
                    'date_soiree' => $b['date_soiree'],
                    'heure_debut' => $b['heure_debut'],
                    'pass_id' => $b['pass_id'] !== null ? (int) $b['pass_id'] : null,
                    'pass_libelle' => $b['pass_libelle'],
                    'categorie' => (string) $b['categorie'],
                    'prix_paye' => (int) $b['prix_paye'],
                    'nom_acheteur' => (string) $b['nom_acheteur'],
                    'emis_le' => (string) $b['emis_le'],
                ];

                if ($b['type'] === 'PASS') {
                    $stmtBilletPass->execute(['bid' => (int) $b['id']]);
                    $bData['soirees'] = $stmtBilletPass->fetchAll(PDO::FETCH_ASSOC);
                }

                $billets[] = $bData;
            }

            $commande['billets'] = $billets;
        }

        return $commande;
    }

    public function enregistrerPaiement(
        string $commandeId,
        string $carteFin,
        array $billets,
        array $jaugesAIncrementer,
    ): void {
        $this->pdo->beginTransaction();

        try {
            // 1. Mettre à jour la commande
            $stmtCommande = $this->pdo->prepare(
                'UPDATE commandes
                 SET statut = "PAYEE", payee_le = NOW(), carte_fin = :carte
                 WHERE id = :id AND statut = "EN_ATTENTE"'
            );
            $stmtCommande->execute([
                'carte' => $carteFin,
                'id' => $commandeId,
            ]);

            // 2. Mettre à jour les jauges des soirées
            $stmtJauge = $this->pdo->prepare(
                'UPDATE soirees
                 SET places_vendues = places_vendues + :qte
                 WHERE id = :id'
            );
            foreach ($jaugesAIncrementer as $soireeId => $quantite) {
                $stmtJauge->execute([
                    'qte' => $quantite,
                    'id' => $soireeId,
                ]);
            }

            // 3. Insérer les billets
            $stmtBillet = $this->pdo->prepare(
                'INSERT INTO billets (token, reference, commande_id, type, soiree_id, pass_id, categorie, prix_paye, nom_acheteur, emis_le)
                 VALUES (:token, :reference, :cid, :type, :sid, :pid, :cat, :prix, :nom, NOW())'
            );

            $stmtBilletPass = $this->pdo->prepare(
                'INSERT INTO billet_pass_soirees (billet_id, soiree_id)
                 VALUES (:bid, :sid)'
            );

            foreach ($billets as $b) {
                $stmtBillet->execute([
                    'token' => $b['token'],
                    'reference' => $b['reference'],
                    'cid' => $commandeId,
                    'type' => $b['type'],
                    'sid' => $b['soiree_id'],
                    'pid' => $b['pass_id'],
                    'cat' => $b['categorie'],
                    'prix' => $b['prix_paye'],
                    'nom' => $b['nom_acheteur'],
                ]);

                $billetId = (int) $this->pdo->lastInsertId();

                if ($b['type'] === 'PASS' && !empty($b['soiree_ids'])) {
                    foreach ($b['soiree_ids'] as $sid) {
                        $stmtBilletPass->execute([
                            'bid' => $billetId,
                            'sid' => $sid,
                        ]);
                    }
                }
            }

            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
