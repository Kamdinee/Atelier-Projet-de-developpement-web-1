<?php

declare(strict_types=1);

namespace Nrv\Domain\ValueObject;

use Nrv\Domain\Entity\Pass;
use Nrv\Domain\Entity\Soiree;
use Nrv\Domain\Exception\DonneeInvalideException;
use Nrv\Domain\Exception\SoireeCompleteException;

// une ligne = n billets identiques : pour une soiree, ou un pass sur 3 soirees distinctes
// le prix vient toujours de la soiree ou du pass en base, jamais du navigateur
final class LigneCommande
{
    public const QUANTITE_MAX = 10;

    /** @var list<SoireeCommandee> */
    private readonly array $soirees;

    /**
     * @param list<SoireeCommandee> $soirees
     */
    public function __construct(
        private readonly TypeBillet $type,
        array $soirees,
        private readonly ?int $passId,
        private readonly ?string $libellePass,
        private readonly CategorieTarif $categorie,
        private readonly int $quantite,
        private readonly int $prixUnitaire,
    ) {
        if ($quantite < 1 || $quantite > self::QUANTITE_MAX) {
            throw new DonneeInvalideException('de 1 a ' . self::QUANTITE_MAX . ' billets par ligne');
        }
        if ($prixUnitaire < 0) {
            throw new DonneeInvalideException('le prix ne peut pas etre negatif');
        }
        $ids = array_map(static fn (SoireeCommandee $s): int => $s->id, $soirees);
        if (count(array_unique($ids)) !== $type->nombreDeSoirees() || count($ids) !== $type->nombreDeSoirees()) {
            throw new DonneeInvalideException("un billet {$type->value} porte sur {$type->nombreDeSoirees()} soiree(s) distincte(s)");
        }
        if (($type === TypeBillet::Pass) !== ($passId !== null)) {
            throw new DonneeInvalideException('seule une ligne pass a une formule de pass');
        }

        usort($soirees, static fn (SoireeCommandee $a, SoireeCommandee $b) => $a->debut <=> $b->debut);
        $this->soirees = $soirees;
    }

    public static function pourSoiree(Soiree $soiree, CategorieTarif $categorie, int $quantite): self
    {
        self::verifierOuverte($soiree);

        return new self(TypeBillet::Soiree, [self::resume($soiree)], null, null, $categorie, $quantite, $soiree->prix($categorie));
    }

    /**
     * @param list<Soiree> $soirees
     */
    public static function pourPass(Pass $pass, array $soirees, CategorieTarif $categorie, int $quantite): self
    {
        $pass->verifierSoirees(array_map(static fn (Soiree $s): int => $s->id(), $soirees));
        array_walk($soirees, static fn (Soiree $s) => self::verifierOuverte($s));

        return new self(
            TypeBillet::Pass,
            array_map(static fn (Soiree $s): SoireeCommandee => self::resume($s), $soirees),
            $pass->id(),
            $pass->libelle(),
            $categorie,
            $quantite,
            $pass->tarifs()->pour($categorie),
        );
    }

    // une soiree complete est refusee des la commande (les places sont reverifiees au paiement)
    private static function verifierOuverte(Soiree $soiree): void
    {
        if ($soiree->estComplete()) {
            throw new SoireeCompleteException($soiree->id(), 1, 0);
        }
    }

    private static function resume(Soiree $soiree): SoireeCommandee
    {
        return new SoireeCommandee($soiree->id(), $soiree->nom(), $soiree->debut());
    }

    public function type(): TypeBillet
    {
        return $this->type;
    }

    /** @return list<SoireeCommandee> */
    public function soirees(): array
    {
        return $this->soirees;
    }

    /** @return list<int> */
    public function soireeIds(): array
    {
        return array_map(static fn (SoireeCommandee $s): int => $s->id, $this->soirees);
    }

    public function passId(): ?int
    {
        return $this->passId;
    }

    public function libellePass(): ?string
    {
        return $this->libellePass;
    }

    public function categorie(): CategorieTarif
    {
        return $this->categorie;
    }

    public function quantite(): int
    {
        return $this->quantite;
    }

    public function prixUnitaire(): int
    {
        return $this->prixUnitaire;
    }

    public function montant(): int
    {
        return $this->quantite * $this->prixUnitaire;
    }
}
