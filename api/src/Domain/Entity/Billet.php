<?php

declare(strict_types=1);

namespace Nrv\Domain\Entity;

use DateTimeImmutable;
use Nrv\Domain\Exception\DonneeInvalideException;
use Nrv\Domain\ValueObject\CategorieTarif;
use Nrv\Domain\ValueObject\JetonBillet;
use Nrv\Domain\ValueObject\SoireeBillet;
use Nrv\Domain\ValueObject\TypeBillet;

// billet emis apres paiement : non nominatif, accessible par son jeton
// un billet soiree couvre 1 soiree, un pass 3 soirees distinctes
final class Billet
{
    /** @var list<SoireeBillet> */
    private readonly array $soirees;

    /**
     * @param list<SoireeBillet> $soirees
     */
    public function __construct(
        private readonly int $id,
        private readonly string $reference,
        private readonly JetonBillet $jeton,
        private readonly string $commandeId,
        private readonly TypeBillet $type,
        private readonly CategorieTarif $categorie,
        private readonly int $prixPaye,
        private readonly string $nomAcheteur,
        private readonly DateTimeImmutable $emisLe,
        array $soirees,
    ) {
        if (trim($reference) === '') {
            throw new DonneeInvalideException('un billet doit avoir une reference');
        }
        if ($prixPaye < 0) {
            throw new DonneeInvalideException('le prix paye ne peut pas etre negatif');
        }
        if (trim($nomAcheteur) === '') {
            throw new DonneeInvalideException("le nom de l'acheteur est obligatoire");
        }

        $ids = [];
        foreach ($soirees as $soiree) {
            if (!$soiree instanceof SoireeBillet) {
                throw new DonneeInvalideException('les soirees du billet doivent etre des SoireeBillet');
            }
            $ids[$soiree->id()] = true;
        }
        $attendu = $type->nombreDeSoirees();
        if (count($soirees) !== $attendu || count($ids) !== $attendu) {
            throw new DonneeInvalideException(
                "un billet {$type->value} donne acces a {$attendu} soiree(s) distincte(s)"
            );
        }

        // soirees dans l'ordre chronologique
        usort($soirees, static fn (SoireeBillet $a, SoireeBillet $b) => $a->debut() <=> $b->debut());
        $this->soirees = $soirees;
    }

    public function id(): int
    {
        return $this->id;
    }

    public function reference(): string
    {
        return $this->reference;
    }

    public function jeton(): JetonBillet
    {
        return $this->jeton;
    }

    public function commandeId(): string
    {
        return $this->commandeId;
    }

    public function type(): TypeBillet
    {
        return $this->type;
    }

    public function categorie(): CategorieTarif
    {
        return $this->categorie;
    }

    public function prixPaye(): int
    {
        return $this->prixPaye;
    }

    public function nomAcheteur(): string
    {
        return $this->nomAcheteur;
    }

    public function emisLe(): DateTimeImmutable
    {
        return $this->emisLe;
    }

    public function estUnPass(): bool
    {
        return $this->type === TypeBillet::Pass;
    }

    /**
     * @return list<SoireeBillet>
     */
    public function soirees(): array
    {
        return $this->soirees;
    }
}
