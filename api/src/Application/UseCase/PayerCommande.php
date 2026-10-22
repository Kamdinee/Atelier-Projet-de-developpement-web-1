<?php

declare(strict_types=1);

namespace Nrv\Application\UseCase;

use InvalidArgumentException;
use Nrv\Application\Port\Out\CommandeRepositoryInterface;
use Nrv\Application\Port\Out\SoireeRepositoryInterface;
use Nrv\Domain\Exception\PlacesInsuffisantesException;
use Nrv\Domain\ValueObject\Uuid;
use RuntimeException;

final class PayerCommande
{
    public function __construct(
        private readonly CommandeRepositoryInterface $commandeRepository,
        private readonly SoireeRepositoryInterface $soireeRepository,
    ) {
    }

    /**
     * @param int $utilisateurId
     * @param string $commandeId
     * @param array<string, mixed> $donneesPaiement
     * @return array<string, mixed>
     */
    public function executer(
        int $utilisateurId,
        string $commandeId,
        array $donneesPaiement,
    ): array {
        $commande = $this->commandeRepository->trouverParId($commandeId);
        if ($commande === null) {
            throw new RuntimeException("La commande {$commandeId} n'existe pas.");
        }

        if ($commande['utilisateur_id'] !== $utilisateurId) {
            throw new RuntimeException("Vous n'êtes pas autorisé à accéder à cette commande.");
        }

        if ($commande['statut'] !== 'EN_ATTENTE') {
            throw new RuntimeException("Cette commande ne peut plus être payée (statut : {$commande['statut']}).");
        }

        // Validation du paiement simulé
        $nomCarte = trim((string) ($donneesPaiement['nom_carte'] ?? ''));
        $numeroBrut = (string) ($donneesPaiement['numero_carte'] ?? '');
        $numeroNettoye = preg_replace('/\D/', '', $numeroBrut);
        $dateExp = trim((string) ($donneesPaiement['date_expiration'] ?? ''));
        $cvv = trim((string) ($donneesPaiement['cvv'] ?? ''));

        if ($nomCarte === '') {
            throw new InvalidArgumentException('Le nom du titulaire de la carte est obligatoire.');
        }

        if (strlen($numeroNettoye) < 12 || strlen($numeroNettoye) > 19) {
            throw new InvalidArgumentException('Le numéro de carte bancaire est invalide.');
        }

        if (!preg_match('/^(0[1-9]|1[0-2])\/?([0-9]{2}|[0-9]{4})$/', $dateExp)) {
            throw new InvalidArgumentException("La date d'expiration doit être au format MM/AA.");
        }

        if (!preg_match('/^\d{3,4}$/', $cvv)) {
            throw new InvalidArgumentException('Le code de sécurité (CVV) doit comporter 3 ou 4 chiffres.');
        }

        // Vérification des places disponibles pour toutes les soirées
        $jaugesAIncrementer = []; // [soiree_id => quantite_cumulee]
        foreach ($commande['lignes'] as $ligne) {
            $qte = (int) $ligne['quantite'];
            if ($ligne['type'] === 'SOIREE') {
                $sid = (int) $ligne['soiree_id'];
                $jaugesAIncrementer[$sid] = ($jaugesAIncrementer[$sid] ?? 0) + $qte;
            } else {
                // PASS
                foreach ($ligne['soirees'] as $s) {
                    $sid = (int) $s['soiree_id'];
                    $jaugesAIncrementer[$sid] = ($jaugesAIncrementer[$sid] ?? 0) + $qte;
                }
            }
        }

        foreach ($jaugesAIncrementer as $sid => $qteRequise) {
            $soiree = $this->soireeRepository->trouverParId($sid);
            if ($soiree === null) {
                throw new RuntimeException("Soirée #{$sid} introuvable.");
            }
            if (!$soiree->peutAccueillir($qteRequise)) {
                throw new PlacesInsuffisantesException($sid, $qteRequise, $soiree->placesRestantes());
            }
        }

        // Génération des billets
        $carteFin = substr($numeroNettoye, -4);
        $nomAcheteur = $nomCarte;
        $billets = [];

        foreach ($commande['lignes'] as $ligne) {
            $quantite = (int) $ligne['quantite'];
            $soireeIds = [];
            if ($ligne['type'] === 'PASS') {
                $soireeIds = array_map(static fn ($s) => (int) $s['soiree_id'], $ligne['soirees']);
            }

            for ($k = 0; $k < $quantite; $k++) {
                $reference = 'BIL-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
                $billets[] = [
                    'token' => Uuid::v4(),
                    'reference' => $reference,
                    'commande_id' => $commandeId,
                    'type' => $ligne['type'],
                    'soiree_id' => $ligne['type'] === 'SOIREE' ? (int) $ligne['soiree_id'] : null,
                    'pass_id' => $ligne['type'] === 'PASS' ? (int) $ligne['pass_id'] : null,
                    'categorie' => $ligne['categorie'],
                    'prix_paye' => (int) $ligne['prix_unitaire'],
                    'nom_acheteur' => $nomAcheteur,
                    'soiree_ids' => $soireeIds,
                ];
            }
        }

        // Enregistrer en base
        $this->commandeRepository->enregistrerPaiement(
            $commandeId,
            $carteFin,
            $billets,
            $jaugesAIncrementer,
        );

        return $this->commandeRepository->trouverParId($commandeId);
    }
}
