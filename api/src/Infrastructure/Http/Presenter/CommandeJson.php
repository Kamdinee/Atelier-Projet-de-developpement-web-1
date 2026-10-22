<?php

declare(strict_types=1);

namespace Nrv\Infrastructure\Http\Presenter;

use Nrv\Application\Dto\CommandeDetailDto;
use Nrv\Application\Dto\LigneCommandeDto;
use Nrv\Infrastructure\Http\Lien;

// format json d'une commande et de ses lignes
final class CommandeJson
{
    public static function detail(CommandeDetailDto $c): array
    {
        $liens = ['self' => Lien::commande($c->id)];
        if ($c->statut === 'EN_ATTENTE') {
            $liens['paiement'] = Lien::paiement($c->id);
        }
        if ($c->statut === 'PAYEE') {
            $liens['billets'] = Lien::billetsCommande($c->id);
        }

        return [
            'commande' => [
                'id' => $c->id,
                'reference' => $c->reference,
                'statut' => $c->statut,
                'total' => $c->total,
                'nb_billets' => $c->nombreBillets,
                'cree_le' => $c->creeLe,
                'payee_le' => $c->payeeLe,
                'carte_fin' => $c->carteFin,
                'lignes' => array_map(static fn (LigneCommandeDto $l): array => [
                    'type' => $l->type,
                    'libelle' => $l->libelle,
                    'categorie' => $l->categorie,
                    'quantite' => $l->quantite,
                    'prix_unitaire' => $l->prixUnitaire,
                    'montant' => $l->montant,
                    'soirees' => $l->soirees,
                ], $c->lignes),
            ],
            'links' => $liens,
        ];
    }
}
