<?php

declare(strict_types=1);

namespace Nrv\Application\Dto;

use Nrv\Domain\Entity\Billet;
use Nrv\Domain\ValueObject\SoireeBillet;

// billet envoye a l'adaptateur http
final class BilletDto
{
    /**
     * @param list<array{id: int, nom: string, date: string, heure_debut: string, lieu: array{nom: string, adresse: string}, spectacles: list<array{titre: string, horaire: string}>}> $soirees
     */
    public function __construct(
        public readonly string $reference,
        public readonly string $jeton,
        public readonly string $commandeId,
        public readonly string $type,
        public readonly string $categorie,
        public readonly int $prix,
        public readonly string $nomAcheteur,
        public readonly string $emisLe,
        public readonly array $soirees,
    ) {
    }

    public static function depuis(Billet $billet): self
    {
        return new self(
            $billet->reference(),
            $billet->jeton()->valeur(),
            $billet->commandeId(),
            $billet->type()->value,
            $billet->categorie()->value,
            $billet->prixPaye(),
            $billet->nomAcheteur(),
            $billet->emisLe()->format('Y-m-d\TH:i:s'),
            array_map(static fn (SoireeBillet $s): array => [
                'id' => $s->id(),
                'nom' => $s->nom(),
                'date' => $s->debut()->format('Y-m-d'),
                'heure_debut' => $s->debut()->format('H:i'),
                'lieu' => ['nom' => $s->lieu(), 'adresse' => $s->adresse()],
                'spectacles' => $s->spectacles(),
            ], $billet->soirees()),
        );
    }
}
