<?php

declare(strict_types=1);

namespace Nrv\Application\UseCase;

use Nrv\Application\Dto\BilletDto;
use Nrv\Application\Dto\CommandeDetailDto;
use Nrv\Application\Dto\PaiementDto;
use Nrv\Application\Port\In\PayerCommandeInterface;
use Nrv\Application\Port\Out\BilletRepositoryInterface;
use Nrv\Application\Port\Out\CommandeRepositoryInterface;
use Nrv\Application\Port\Out\GenerateurIdInterface;
use Nrv\Application\Port\Out\HorlogeInterface;
use Nrv\Application\Port\Out\PasserelleBancaireInterface;
use Nrv\Application\Port\Out\SoireeRepositoryInterface;
use Nrv\Application\Port\Out\TransactionInterface;
use Nrv\Application\Port\Out\UtilisateurRepositoryInterface;
use Nrv\Domain\Entity\Billet;
use Nrv\Domain\Entity\Commande;
use Nrv\Domain\Exception\CommandeIntrouvableException;
use Nrv\Domain\Exception\SoireeIntrouvableException;
use Nrv\Domain\Exception\UtilisateurIntrouvableException;
use Nrv\Domain\ValueObject\CarteBancaire;
use Nrv\Domain\ValueObject\JetonBillet;

// tout se passe dans une transaction : au premier refus (deja payee, places, banque), rien n'est enregistre
final class PayerCommande implements PayerCommandeInterface
{
    public function __construct(
        private readonly TransactionInterface $transaction,
        private readonly CommandeRepositoryInterface $commandes,
        private readonly SoireeRepositoryInterface $soirees,
        private readonly UtilisateurRepositoryInterface $utilisateurs,
        private readonly BilletRepositoryInterface $billets,
        private readonly PasserelleBancaireInterface $banque,
        private readonly GenerateurIdInterface $ids,
        private readonly HorlogeInterface $horloge,
    ) {
    }

    public function executer(int $utilisateurId, string $commandeId, CarteBancaire $carte): PaiementDto
    {
        $commande = $this->transaction->executer(function () use ($utilisateurId, $commandeId, $carte): Commande {
            // 1. la commande, verrouillee : un double clic attend puis voit qu'elle est deja payee
            $commande = $this->commandes->trouverPourPaiement($commandeId);
            if ($commande === null || !$commande->appartientA($utilisateurId)) {
                throw CommandeIntrouvableException::pour($commandeId);
            }
            $commande->payer($carte, $this->horloge->maintenant());

            // 2. les soirees, verrouillees : les places sont reservees par le domaine (refus si plus assez)
            $places = $commande->placesParSoiree();
            $soirees = $this->soirees->verrouiller(array_keys($places));
            foreach ($places as $soireeId => $nombre) {
                ($soirees[$soireeId] ?? throw SoireeIntrouvableException::avecId($soireeId))->reserver($nombre);
            }

            // 3. le debit, seulement quand on sait que tout peut etre servi
            $this->banque->debiter($carte, $commande->total());

            // 4. enregistrement : places vendues, commande payee, un billet par place
            $acheteur = $this->utilisateurs->trouverParId($utilisateurId)
                ?? throw UtilisateurIntrouvableException::avecId($utilisateurId);
            foreach ($soirees as $soiree) {
                $this->soirees->enregistrerPlacesVendues($soiree);
            }
            $this->commandes->enregistrerPaiement($commande, $commande->billetsAEmettre(
                $acheteur->prenom() . ' ' . $acheteur->nom(),
                fn (): JetonBillet => new JetonBillet($this->ids->uuid()),
            ));

            return $commande;
        });

        return new PaiementDto(
            CommandeDetailDto::depuis($commande),
            array_map(static fn (Billet $b): BilletDto => BilletDto::depuis($b), $this->billets->billetsDeCommande($commandeId)),
        );
    }
}
