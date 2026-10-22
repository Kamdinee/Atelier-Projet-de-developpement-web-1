<?php

declare(strict_types=1);

namespace Nrv\Infrastructure\Persistence\Pdo;

use DateTimeImmutable;
use Nrv\Application\Port\Out\CommandeRepositoryInterface;
use Nrv\Domain\Entity\Commande;
use Nrv\Domain\Exception\CommandeDejaPayeeException;
use Nrv\Domain\ValueObject\CategorieTarif;
use Nrv\Domain\ValueObject\LigneCommande;
use Nrv\Domain\ValueObject\SoireeCommandee;
use Nrv\Domain\ValueObject\StatutCommande;
use Nrv\Domain\ValueObject\TypeBillet;
use PDO;

// adaptateur pdo du port CommandeRepositoryInterface
// tables commandes, lignes_commande, ligne_pass_soirees, billets, billet_pass_soirees
final class PDOCommandeRepository implements CommandeRepositoryInterface
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function ajouter(Commande $commande): void
    {
        $insCommande = $this->pdo->prepare(
            'INSERT INTO commandes (id, utilisateur_id, statut, total, cree_le) VALUES (:id, :utilisateur, :statut, :total, :cree_le)'
        );
        $insLigne = $this->pdo->prepare(
            'INSERT INTO lignes_commande (commande_id, type, soiree_id, pass_id, categorie, quantite, prix_unitaire)
            VALUES (:commande, :type, :soiree, :pass, :categorie, :quantite, :prix)'
        );
        $insPassSoiree = $this->pdo->prepare('INSERT INTO ligne_pass_soirees (ligne_id, soiree_id) VALUES (:ligne, :soiree)');

        $this->pdo->beginTransaction();
        try {
            $insCommande->bindValue('id', $commande->id());
            $insCommande->bindValue('utilisateur', $commande->utilisateurId(), PDO::PARAM_INT);
            $insCommande->bindValue('statut', $commande->statut()->value);
            $insCommande->bindValue('total', $commande->total(), PDO::PARAM_INT);
            $insCommande->bindValue('cree_le', $commande->creeLe()->format('Y-m-d H:i:s'));
            $insCommande->execute();

            foreach ($commande->lignes() as $ligne) {
                $pass = $ligne->type() === TypeBillet::Pass;
                $insLigne->bindValue('commande', $commande->id());
                $insLigne->bindValue('type', $ligne->type()->value);
                $insLigne->bindValue('soiree', $pass ? null : $ligne->soireeIds()[0], $pass ? PDO::PARAM_NULL : PDO::PARAM_INT);
                $insLigne->bindValue('pass', $ligne->passId(), $pass ? PDO::PARAM_INT : PDO::PARAM_NULL);
                $insLigne->bindValue('categorie', $ligne->categorie()->value);
                $insLigne->bindValue('quantite', $ligne->quantite(), PDO::PARAM_INT);
                $insLigne->bindValue('prix', $ligne->prixUnitaire(), PDO::PARAM_INT);
                $insLigne->execute();

                if ($pass) {
                    $ligneId = (int) $this->pdo->lastInsertId();
                    foreach ($ligne->soireeIds() as $soireeId) {
                        $insPassSoiree->bindValue('ligne', $ligneId, PDO::PARAM_INT);
                        $insPassSoiree->bindValue('soiree', $soireeId, PDO::PARAM_INT);
                        $insPassSoiree->execute();
                    }
                }
            }
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function trouverParId(string $id): ?Commande
    {
        return $this->charger($id, false);
    }

    public function trouverPourPaiement(string $id): ?Commande
    {
        return $this->charger($id, true);
    }

    public function enregistrerPaiement(Commande $commande, array $billets): void
    {
        // garde en base : une commande deja payee n'est jamais repayee
        $maj = $this->pdo->prepare(
            "UPDATE commandes SET statut = :statut, carte_fin = :carte, payee_le = :payee_le
            WHERE id = :id AND statut = 'EN_ATTENTE'"
        );
        $maj->bindValue('statut', $commande->statut()->value);
        $maj->bindValue('carte', $commande->carteFin());
        $maj->bindValue('payee_le', $commande->payeeLe()?->format('Y-m-d H:i:s'));
        $maj->bindValue('id', $commande->id());
        $maj->execute();
        if ($maj->rowCount() !== 1) {
            throw new CommandeDejaPayeeException();
        }

        $insBillet = $this->pdo->prepare(
            'INSERT INTO billets (token, reference, commande_id, type, soiree_id, pass_id, categorie, prix_paye, nom_acheteur, emis_le)
            VALUES (:token, :reference, :commande, :type, :soiree, :pass, :categorie, :prix, :nom, :emis_le)'
        );
        $insPassSoiree = $this->pdo->prepare('INSERT INTO billet_pass_soirees (billet_id, soiree_id) VALUES (:billet, :soiree)');

        foreach ($billets as $b) {
            $pass = $b->type === TypeBillet::Pass;
            $insBillet->bindValue('token', $b->jeton->valeur());
            $insBillet->bindValue('reference', $b->reference);
            $insBillet->bindValue('commande', $commande->id());
            $insBillet->bindValue('type', $b->type->value);
            $insBillet->bindValue('soiree', $pass ? null : $b->soireeIds[0], $pass ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $insBillet->bindValue('pass', $b->passId, $pass ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $insBillet->bindValue('categorie', $b->categorie->value);
            $insBillet->bindValue('prix', $b->prixPaye, PDO::PARAM_INT);
            $insBillet->bindValue('nom', $b->nomAcheteur);
            $insBillet->bindValue('emis_le', $commande->payeeLe()?->format('Y-m-d H:i:s'));
            $insBillet->execute();

            if ($pass) {
                $billetId = (int) $this->pdo->lastInsertId();
                foreach ($b->soireeIds as $soireeId) {
                    $insPassSoiree->bindValue('billet', $billetId, PDO::PARAM_INT);
                    $insPassSoiree->bindValue('soiree', $soireeId, PDO::PARAM_INT);
                    $insPassSoiree->execute();
                }
            }
        }
    }

    private function charger(string $id, bool $verrouiller): ?Commande
    {
        $requete = $this->pdo->prepare(
            'SELECT id, utilisateur_id, statut, carte_fin, cree_le, payee_le FROM commandes WHERE id = :id'
            . ($verrouiller ? ' FOR UPDATE' : '')
        );
        $requete->bindValue('id', $id);
        $requete->execute();
        $c = $requete->fetch(PDO::FETCH_ASSOC);
        if ($c === false) {
            return null;
        }

        // lignes avec leur soiree (billet soiree) ou les 3 soirees du pass
        $requete = $this->pdo->prepare(
            'SELECT lc.id AS ligne_id, lc.type, lc.pass_id, p.libelle AS pass_libelle, lc.categorie, lc.quantite,
                lc.prix_unitaire, s.id AS soiree_id, s.nom AS soiree_nom, s.date_soiree, s.heure_debut
            FROM lignes_commande lc
            LEFT JOIN pass p ON p.id = lc.pass_id
            LEFT JOIN ligne_pass_soirees lps ON lps.ligne_id = lc.id
            JOIN soirees s ON s.id = COALESCE(lc.soiree_id, lps.soiree_id)
            WHERE lc.commande_id = :id
            ORDER BY lc.id, s.date_soiree'
        );
        $requete->bindValue('id', $id);
        $requete->execute();

        $lignes = [];
        foreach ($requete->fetchAll(PDO::FETCH_ASSOC) as $l) {
            $lignes[(int) $l['ligne_id']]['ligne'] = $l;
            $lignes[(int) $l['ligne_id']]['soirees'][] = new SoireeCommandee(
                (int) $l['soiree_id'],
                (string) $l['soiree_nom'],
                new DateTimeImmutable($l['date_soiree'] . ' ' . $l['heure_debut']),
            );
        }

        return new Commande(
            (string) $c['id'],
            (int) $c['utilisateur_id'],
            array_values(array_map(static fn (array $g): LigneCommande => new LigneCommande(
                TypeBillet::from((string) $g['ligne']['type']),
                $g['soirees'],
                $g['ligne']['pass_id'] === null ? null : (int) $g['ligne']['pass_id'],
                $g['ligne']['pass_libelle'],
                CategorieTarif::from((string) $g['ligne']['categorie']),
                (int) $g['ligne']['quantite'],
                (int) $g['ligne']['prix_unitaire'],
            ), $lignes)),
            new DateTimeImmutable((string) $c['cree_le']),
            StatutCommande::from((string) $c['statut']),
            $c['carte_fin'] === null ? null : (string) $c['carte_fin'],
            $c['payee_le'] === null ? null : new DateTimeImmutable((string) $c['payee_le']),
        );
    }
}
