<?php

declare(strict_types=1);

namespace Nrv\Domain\ValueObject;

// billet a emettre au paiement (l'id est donne par la base a l'enregistrement)
final class NouveauBillet
{
    /**
     * @param list<int> $soireeIds 1 soiree, ou les 3 soirees d'un pass
     */
    public function __construct(
        public readonly JetonBillet $jeton,
        public readonly string $reference,
        public readonly TypeBillet $type,
        public readonly array $soireeIds,
        public readonly ?int $passId,
        public readonly CategorieTarif $categorie,
        public readonly int $prixPaye,
        public readonly string $nomAcheteur,
    ) {
    }
}
