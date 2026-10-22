<?php

declare(strict_types=1);

namespace Nrv\Application\UseCase;

use Nrv\Application\Dto\CommandeDetailDto;
use Nrv\Application\Dto\LigneDemandeeDto;
use Nrv\Application\Port\In\CreerCommandeInterface;
use Nrv\Application\Port\Out\CommandeRepositoryInterface;
use Nrv\Application\Port\Out\GenerateurIdInterface;
use Nrv\Application\Port\Out\HorlogeInterface;
use Nrv\Application\Port\Out\PassRepositoryInterface;
use Nrv\Application\Port\Out\SoireeRepositoryInterface;
use Nrv\Domain\Entity\Commande;
use Nrv\Domain\Entity\Soiree;
use Nrv\Domain\Exception\DonneeInvalideException;
use Nrv\Domain\Exception\PlacesInsuffisantesException;
use Nrv\Domain\Exception\SoireeIntrouvableException;
use Nrv\Domain\ValueObject\CategorieTarif;
use Nrv\Domain\ValueObject\LigneCommande;
use Nrv\Domain\ValueObject\TypeBillet;

final class CreerCommande implements CreerCommandeInterface
{
    /** @var array<int, Soiree> soirees deja lues pendant la commande */
    private array $soireesLues = [];

    public function __construct(
        private readonly SoireeRepositoryInterface $soirees,
        private readonly PassRepositoryInterface $pass,
        private readonly CommandeRepositoryInterface $commandes,
        private readonly GenerateurIdInterface $ids,
        private readonly HorlogeInterface $horloge,
    ) {
    }

    public function executer(int $utilisateurId, array $lignes): CommandeDetailDto
    {
        $this->soireesLues = [];
        $commande = new Commande(
            $this->ids->uuid(),
            $utilisateurId,
            array_map(fn (LigneDemandeeDto $l): LigneCommande => $this->ligne($l), $lignes),
            $this->horloge->maintenant(),
        );

        // premier controle des places (le controle definitif est fait au paiement, sous verrou)
        foreach ($commande->placesParSoiree() as $soireeId => $places) {
            $soiree = $this->soireesLues[$soireeId];
            if (!$soiree->peutAccueillir($places)) {
                throw new PlacesInsuffisantesException($soireeId, $places, $soiree->placesRestantes());
            }
        }

        $this->commandes->ajouter($commande);

        return CommandeDetailDto::depuis($commande);
    }

    private function ligne(LigneDemandeeDto $l): LigneCommande
    {
        $categorie = CategorieTarif::tryFrom($l->categorie)
            ?? throw new DonneeInvalideException('categorie de tarif inconnue');

        return match (TypeBillet::tryFrom($l->type)) {
            TypeBillet::Soiree => LigneCommande::pourSoiree($this->soiree($l->soireeIds[0] ?? 0), $categorie, $l->quantite),
            TypeBillet::Pass => LigneCommande::pourPass(
                $this->pass->trouverParId((int) $l->passId) ?? throw new DonneeInvalideException('formule de pass inconnue'),
                array_map(fn (int $id): Soiree => $this->soiree($id), $l->soireeIds),
                $categorie,
                $l->quantite,
            ),
            default => throw new DonneeInvalideException('type de billet inconnu'),
        };
    }

    private function soiree(int $id): Soiree
    {
        return $this->soireesLues[$id] ??= $this->soirees->trouverParId($id)
            ?? throw SoireeIntrouvableException::avecId($id);
    }
}
