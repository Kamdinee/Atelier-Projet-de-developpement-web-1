<?php

declare(strict_types=1);

namespace Nrv\Application\UseCase;

use Nrv\Application\Dto\BilletDto;
use Nrv\Application\Dto\CommandeDto;
use Nrv\Application\Port\In\ConsulterMesAchatsInterface;
use Nrv\Application\Port\Out\BilletRepositoryInterface;
use Nrv\Application\Port\Out\CommandeLectureRepositoryInterface;
use Nrv\Domain\Entity\Billet;
use Nrv\Domain\Exception\CommandeIntrouvableException;
use Nrv\Domain\ValueObject\CommandeResume;

final class ConsulterMesAchats implements ConsulterMesAchatsInterface
{
    public function __construct(
        private readonly CommandeLectureRepositoryInterface $commandes,
        private readonly BilletRepositoryInterface $billets,
    ) {
    }

    public function commandes(int $utilisateurId): array
    {
        return array_map(
            static fn (CommandeResume $c): CommandeDto => CommandeDto::depuis($c),
            $this->commandes->commandesDe($utilisateurId),
        );
    }

    public function billetsDeCommande(int $utilisateurId, string $commandeId): array
    {
        // la commande d'un autre client est traitee comme inexistante (on ne revele pas qu'elle existe)
        if ($this->commandes->proprietaire($commandeId) !== $utilisateurId) {
            throw CommandeIntrouvableException::pour($commandeId);
        }

        return $this->versDto($this->billets->billetsDeCommande($commandeId));
    }

    public function billets(int $utilisateurId): array
    {
        return $this->versDto($this->billets->billetsDeUtilisateur($utilisateurId));
    }

    /**
     * @param list<Billet> $billets
     * @return list<BilletDto>
     */
    private function versDto(array $billets): array
    {
        return array_map(static fn (Billet $b): BilletDto => BilletDto::depuis($b), $billets);
    }
}
