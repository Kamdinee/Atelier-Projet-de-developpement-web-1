<?php

declare(strict_types=1);

namespace Nrv\Application\Dto;

use Nrv\Domain\ValueObject\CommandeResume;

final class CommandeDto
{
    public function __construct(
        public readonly string $id,
        public readonly string $statut,
        public readonly int $total,
        public readonly int $nombreBillets,
        public readonly string $creeLe,
        public readonly ?string $payeeLe,
    ) {
    }

    public static function depuis(CommandeResume $commande): self
    {
        return new self(
            $commande->id(),
            $commande->statut(),
            $commande->total(),
            $commande->nombreBillets(),
            $commande->creeLe()->format('Y-m-d\TH:i:s'),
            $commande->payeeLe()?->format('Y-m-d\TH:i:s'),
        );
    }
}
