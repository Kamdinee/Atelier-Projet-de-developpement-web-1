<?php

declare(strict_types=1);

namespace Nrv\Domain\Entity;

use Closure;
use DateTimeImmutable;
use Nrv\Domain\Exception\CommandeDejaPayeeException;
use Nrv\Domain\Exception\DonneeInvalideException;
use Nrv\Domain\ValueObject\CarteBancaire;
use Nrv\Domain\ValueObject\JetonBillet;
use Nrv\Domain\ValueObject\LigneCommande;
use Nrv\Domain\ValueObject\NouveauBillet;
use Nrv\Domain\ValueObject\StatutCommande;

// commande d'un client : creee "en attente" depuis le panier, puis payee (paiement simule)
// au paiement, chaque billet de chaque ligne devient un billet emis avec son jeton
final class Commande
{
    public const LIGNES_MAX = 20;

    /** @var list<LigneCommande> */
    private readonly array $lignes;

    private StatutCommande $statut;
    private ?string $carteFin;
    private ?DateTimeImmutable $payeeLe;

    /**
     * @param list<LigneCommande> $lignes
     */
    public function __construct(
        private readonly string $id,
        private readonly int $utilisateurId,
        array $lignes,
        private readonly DateTimeImmutable $creeLe,
        StatutCommande $statut = StatutCommande::EnAttente,
        ?string $carteFin = null,
        ?DateTimeImmutable $payeeLe = null,
    ) {
        if ($lignes === [] || count($lignes) > self::LIGNES_MAX) {
            throw new DonneeInvalideException('une commande a de 1 a ' . self::LIGNES_MAX . ' lignes');
        }
        foreach ($lignes as $ligne) {
            if (!$ligne instanceof LigneCommande) {
                throw new DonneeInvalideException('ligne de commande invalide');
            }
        }
        $this->lignes = array_values($lignes);
        $this->statut = $statut;
        $this->carteFin = $carteFin;
        $this->payeeLe = $payeeLe;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function utilisateurId(): int
    {
        return $this->utilisateurId;
    }

    public function appartientA(int $utilisateurId): bool
    {
        return $this->utilisateurId === $utilisateurId;
    }

    /** @return list<LigneCommande> */
    public function lignes(): array
    {
        return $this->lignes;
    }

    public function statut(): StatutCommande
    {
        return $this->statut;
    }

    public function carteFin(): ?string
    {
        return $this->carteFin;
    }

    public function creeLe(): DateTimeImmutable
    {
        return $this->creeLe;
    }

    public function payeeLe(): ?DateTimeImmutable
    {
        return $this->payeeLe;
    }

    public function total(): int
    {
        return array_sum(array_map(static fn (LigneCommande $l): int => $l->montant(), $this->lignes));
    }

    public function nombreBillets(): int
    {
        return array_sum(array_map(static fn (LigneCommande $l): int => $l->quantite(), $this->lignes));
    }

    // places a reserver par soiree : un billet soiree en prend une, un pass une dans chacune de ses 3 soirees
    /** @return array<int, int> soiree => places */
    public function placesParSoiree(): array
    {
        $places = [];
        foreach ($this->lignes as $ligne) {
            foreach ($ligne->soireeIds() as $soireeId) {
                $places[$soireeId] = ($places[$soireeId] ?? 0) + $ligne->quantite();
            }
        }
        ksort($places);

        return $places;
    }

    // la commande ne peut etre payee qu'une fois, avec une carte encore valable
    // (le debit lui-meme est fait par la passerelle bancaire, hors du domaine)
    public function payer(CarteBancaire $carte, DateTimeImmutable $maintenant): void
    {
        if ($this->statut !== StatutCommande::EnAttente) {
            throw new CommandeDejaPayeeException();
        }
        if ($carte->estExpiree($maintenant)) {
            throw new DonneeInvalideException('carte expiree');
        }

        $this->statut = StatutCommande::Payee;
        $this->carteFin = $carte->fin();
        $this->payeeLe = $maintenant;
    }

    // reference courte de la commande, reprise sur chaque billet : NRV-1A2B3C4D-01, -02...
    public function reference(): string
    {
        return 'NRV-' . strtoupper(substr($this->id, 0, 8));
    }

    /**
     * un billet par place achetee, chacun avec un jeton aleatoire
     *
     * @param Closure(): JetonBillet $nouveauJeton
     * @return list<NouveauBillet>
     */
    public function billetsAEmettre(string $nomAcheteur, Closure $nouveauJeton): array
    {
        if ($this->statut !== StatutCommande::Payee) {
            throw new DonneeInvalideException('les billets ne sont emis qu\'apres le paiement');
        }

        $billets = [];
        foreach ($this->lignes as $ligne) {
            for ($i = 0; $i < $ligne->quantite(); $i++) {
                $billets[] = new NouveauBillet(
                    $nouveauJeton(),
                    sprintf('%s-%02d', $this->reference(), count($billets) + 1),
                    $ligne->type(),
                    $ligne->soireeIds(),
                    $ligne->passId(),
                    $ligne->categorie(),
                    $ligne->prixUnitaire(),
                    $nomAcheteur,
                );
            }
        }

        return $billets;
    }
}
