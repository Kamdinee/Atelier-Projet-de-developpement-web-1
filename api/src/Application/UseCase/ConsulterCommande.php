<?php

declare(strict_types=1);

namespace Nrv\Application\UseCase;

use Nrv\Application\Dto\CommandeDetailDto;
use Nrv\Application\Port\In\ConsulterCommandeInterface;
use Nrv\Application\Port\Out\CommandeRepositoryInterface;
use Nrv\Domain\Exception\CommandeIntrouvableException;

final class ConsulterCommande implements ConsulterCommandeInterface
{
    public function __construct(private readonly CommandeRepositoryInterface $commandes)
    {
    }

    public function executer(int $utilisateurId, string $commandeId): CommandeDetailDto
    {
        $commande = $this->commandes->trouverParId($commandeId);
        // la commande d'un autre client est traitee comme inexistante
        if ($commande === null || !$commande->appartientA($utilisateurId)) {
            throw CommandeIntrouvableException::pour($commandeId);
        }

        return CommandeDetailDto::depuis($commande);
    }
}
