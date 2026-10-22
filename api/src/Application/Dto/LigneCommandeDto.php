<?php

declare(strict_types=1);

namespace Nrv\Application\Dto;

use Nrv\Domain\ValueObject\LigneCommande;
use Nrv\Domain\ValueObject\SoireeCommandee;
use Nrv\Domain\ValueObject\TypeBillet;

final class LigneCommandeDto
{
    /**
     * @param list<array{id: int, nom: string, date: string, heure: string}> $soirees
     */
    public function __construct(
        public readonly string $type,
        public readonly string $libelle,
        public readonly string $categorie,
        public readonly int $quantite,
        public readonly int $prixUnitaire,
        public readonly int $montant,
        public readonly array $soirees,
    ) {
    }

    public static function depuis(LigneCommande $ligne): self
    {
        $soirees = array_map(static fn (SoireeCommandee $s): array => [
            'id' => $s->id,
            'nom' => $s->nom,
            'date' => $s->debut->format('Y-m-d'),
            'heure' => $s->debut->format('H:i'),
        ], $ligne->soirees());

        return new self(
            $ligne->type()->value,
            $ligne->type() === TypeBillet::Pass ? (string) $ligne->libellePass() : $soirees[0]['nom'],
            $ligne->categorie()->value,
            $ligne->quantite(),
            $ligne->prixUnitaire(),
            $ligne->montant(),
            $soirees,
        );
    }
}
