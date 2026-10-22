<?php

declare(strict_types=1);

namespace Nrv\Application\UseCase;

use InvalidArgumentException;
use Nrv\Application\Port\Out\CommandeRepositoryInterface;
use Nrv\Application\Port\Out\PassRepositoryInterface;
use Nrv\Application\Port\Out\SoireeRepositoryInterface;
use Nrv\Domain\Exception\PlacesInsuffisantesException;
use Nrv\Domain\ValueObject\CategorieTarif;
use Nrv\Domain\ValueObject\Uuid;
use RuntimeException;

final class CreerCommande
{
    public function __construct(
        private readonly CommandeRepositoryInterface $commandeRepository,
        private readonly SoireeRepositoryInterface $soireeRepository,
        private readonly PassRepositoryInterface $passRepository,
    ) {
    }

    /**
     * @param int $utilisateurId
     * @param list<array<string, mixed>> $lignesPanier
     * @return array<string, mixed>
     */
    public function executer(int $utilisateurId, array $lignesPanier): array
    {
        if (empty($lignesPanier)) {
            throw new InvalidArgumentException('Le panier est vide.');
        }

        $lignesValidees = [];
        $total = 0;

        foreach ($lignesPanier as $index => $item) {
            $type = strtoupper((string) ($item['type'] ?? ''));
            $categorieStr = strtoupper((string) ($item['categorie'] ?? 'NORMAL'));
            $quantite = (int) ($item['quantite'] ?? 0);

            if (!in_array($type, ['SOIREE', 'PASS'], true)) {
                throw new InvalidArgumentException("Type de billet invalide à la ligne {$index}.");
            }

            if (!in_array($categorieStr, ['NORMAL', 'REDUIT'], true)) {
                throw new InvalidArgumentException("Catégorie de tarif invalide à la ligne {$index}.");
            }

            if ($quantite < 1 || $quantite > 10) {
                throw new InvalidArgumentException("La quantité doit être comprise entre 1 et 10 à la ligne {$index}.");
            }

            $categorieEnum = $categorieStr === 'REDUIT' ? CategorieTarif::Reduit : CategorieTarif::Normal;

            if ($type === 'SOIREE') {
                $soireeId = (int) ($item['soireeId'] ?? $item['soiree_id'] ?? 0);
                if ($soireeId <= 0) {
                    throw new InvalidArgumentException("Identifiant de soirée manquant à la ligne {$index}.");
                }

                $soiree = $this->soireeRepository->trouverParId($soireeId);
                if ($soiree === null) {
                    throw new RuntimeException("La soirée #{$soireeId} n'existe pas.");
                }

                if (!$soiree->peutAccueillir($quantite)) {
                    throw new PlacesInsuffisantesException($soireeId, $quantite, $soiree->placesRestantes());
                }

                $prixUnitaire = $soiree->prix($categorieEnum);
                $total += $prixUnitaire * $quantite;

                $lignesValidees[] = [
                    'type' => 'SOIREE',
                    'soiree_id' => $soireeId,
                    'pass_id' => null,
                    'categorie' => $categorieStr,
                    'quantite' => $quantite,
                    'prix_unitaire' => $prixUnitaire,
                    'nom' => $soiree->nom(),
                ];
            } else {
                // PASS
                $passId = (int) ($item['passId'] ?? $item['pass_id'] ?? 0);
                $dates = $item['dates'] ?? [];

                if ($passId <= 0) {
                    throw new InvalidArgumentException("Identifiant de Pass manquant à la ligne {$index}.");
                }

                if (!is_array($dates) || count($dates) !== 3 || count(array_unique($dates)) !== 3) {
                    throw new InvalidArgumentException("Un Pass 3 jours requiert exactement 3 dates distinctes à la ligne {$index}.");
                }

                $pass = $this->passRepository->trouverParId($passId);
                if ($pass === null) {
                    throw new RuntimeException("Le Pass #{$passId} n'existe pas.");
                }

                $soireeIds = [];
                foreach ($dates as $dateStr) {
                    $soiree = $this->soireeRepository->trouverParDate((string) $dateStr);
                    if ($soiree === null) {
                        throw new RuntimeException("Aucune soirée n'est programmée pour la date {$dateStr}.");
                    }
                    if (!$soiree->peutAccueillir($quantite)) {
                        throw new PlacesInsuffisantesException($soiree->id(), $quantite, $soiree->placesRestantes());
                    }
                    $soireeIds[] = $soiree->id();
                }

                $prixUnitaire = $categorieStr === 'REDUIT' ? $pass['tarif_reduit'] : $pass['tarif_normal'];
                $total += $prixUnitaire * $quantite;

                $lignesValidees[] = [
                    'type' => 'PASS',
                    'soiree_id' => null,
                    'pass_id' => $passId,
                    'categorie' => $categorieStr,
                    'quantite' => $quantite,
                    'prix_unitaire' => $prixUnitaire,
                    'soiree_ids' => $soireeIds,
                    'nom' => $pass['libelle'],
                ];
            }
        }

        $commandeId = Uuid::v4();
        $commandeData = [
            'id' => $commandeId,
            'utilisateur_id' => $utilisateurId,
            'statut' => 'EN_ATTENTE',
            'total' => $total,
        ];

        $this->commandeRepository->creer($commandeData, $lignesValidees);

        return $this->commandeRepository->trouverParId($commandeId);
    }
}
