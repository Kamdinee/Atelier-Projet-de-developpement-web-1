<?php

declare(strict_types=1);

namespace Nrv\Application\Port\Out;

interface CommandeRepositoryInterface
{
    /**
     * @param array{
     *     id: string,
     *     utilisateur_id: int,
     *     statut: string,
     *     total: int
     * } $commande
     * @param list<array{
     *     type: string,
     *     soiree_id: ?int,
     *     pass_id: ?int,
     *     categorie: string,
     *     quantite: int,
     *     prix_unitaire: int,
     *     soiree_ids?: list<int>
     * }> $lignes
     */
    public function creer(array $commande, array $lignes): void;

    /**
     * @return array{
     *     id: string,
     *     utilisateur_id: int,
     *     statut: string,
     *     total: int,
     *     carte_fin: ?string,
     *     cree_le: string,
     *     payee_le: ?string,
     *     lignes: list<array<string, mixed>>,
     *     billets?: list<array<string, mixed>>
     * }|null
     */
    public function trouverParId(string $id): ?array;

    /**
     * @param string $commandeId
     * @param string $carteFin
     * @param list<array{
     *     token: string,
     *     reference: string,
     *     commande_id: string,
     *     type: string,
     *     soiree_id: ?int,
     *     pass_id: ?int,
     *     categorie: string,
     *     prix_paye: int,
     *     nom_acheteur: string,
     *     soiree_ids?: list<int>
     * }> $billets
     * @param array<int, int> $jaugesAIncrementer Map [soiree_id => quantite_places]
     */
    public function enregistrerPaiement(
        string $commandeId,
        string $carteFin,
        array $billets,
        array $jaugesAIncrementer,
    ): void;
}
