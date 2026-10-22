<?php

declare(strict_types=1);

namespace Nrv\Application\Dto;

// ligne du panier envoyee par le client : seuls les identifiants, la categorie et la quantite comptent
// (le prix envoye par le navigateur est ignore, il est relu en base)
final class LigneDemandeeDto
{
    /**
     * @param list<int> $soireeIds 1 soiree, ou les 3 soirees d'un pass
     */
    public function __construct(
        public readonly string $type,
        public readonly array $soireeIds,
        public readonly ?int $passId,
        public readonly string $categorie,
        public readonly int $quantite,
    ) {
    }
}
