<?php

declare(strict_types=1);

namespace Nrv\Infrastructure\Http\Presenter;

use Nrv\Application\Dto\BilletDto;
use Nrv\Infrastructure\Http\Lien;

// format json d'un billet : tout ce qui est imprime dessus (F10)
final class BilletJson
{
    public static function complet(BilletDto $b): array
    {
        return [
            'billet' => [
                'reference' => $b->reference,
                'token' => $b->jeton,
                'type' => $b->type,
                'categorie' => $b->categorie,
                'prix' => $b->prix,
                'nom_acheteur' => $b->nomAcheteur,
                'emis_le' => $b->emisLe,
                'commande_id' => $b->commandeId,
                'soirees' => array_map(
                    static fn (array $s): array => $s + ['links' => ['self' => Lien::soiree($s['id'])]],
                    $b->soirees,
                ),
            ],
            'links' => [
                'self' => Lien::billet($b->jeton),
            ],
        ];
    }

    /**
     * @param list<BilletDto> $billets
     */
    public static function collection(array $billets): array
    {
        return [
            'type' => 'collection',
            'count' => count($billets),
            'billets' => array_map(static fn (BilletDto $b): array => self::complet($b), $billets),
        ];
    }
}
