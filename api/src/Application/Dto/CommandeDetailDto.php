<?php

declare(strict_types=1);

namespace Nrv\Application\Dto;

use Nrv\Domain\Entity\Commande;
use Nrv\Domain\ValueObject\LigneCommande;

// une commande et ses lignes (recapitulatif avant paiement, confirmation)
final class CommandeDetailDto
{
    /**
     * @param list<LigneCommandeDto> $lignes
     */
    public function __construct(
        public readonly string $id,
        public readonly string $reference,
        public readonly string $statut,
        public readonly int $total,
        public readonly int $nombreBillets,
        public readonly string $creeLe,
        public readonly ?string $payeeLe,
        public readonly ?string $carteFin,
        public readonly array $lignes,
    ) {
    }

    public static function depuis(Commande $c): self
    {
        return new self(
            $c->id(),
            $c->reference(),
            $c->statut()->value,
            $c->total(),
            $c->nombreBillets(),
            $c->creeLe()->format('Y-m-d\TH:i:s'),
            $c->payeeLe()?->format('Y-m-d\TH:i:s'),
            $c->carteFin(),
            array_map(static fn (LigneCommande $l): LigneCommandeDto => LigneCommandeDto::depuis($l), $c->lignes()),
        );
    }
}
