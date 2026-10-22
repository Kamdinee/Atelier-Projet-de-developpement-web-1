<?php

declare(strict_types=1);

namespace Nrv\Application\UseCase;

use Nrv\Application\Dto\UtilisateurDto;
use Nrv\Application\Port\In\ConsulterCompteInterface;
use Nrv\Application\Port\Out\UtilisateurRepositoryInterface;
use Nrv\Domain\Exception\UtilisateurIntrouvableException;

final class ConsulterCompte implements ConsulterCompteInterface
{
    public function __construct(private readonly UtilisateurRepositoryInterface $utilisateurs)
    {
    }

    public function executer(int $utilisateurId): UtilisateurDto
    {
        $utilisateur = $this->utilisateurs->trouverParId($utilisateurId);
        if ($utilisateur === null) {
            throw UtilisateurIntrouvableException::avecId($utilisateurId);
        }

        return UtilisateurDto::depuis($utilisateur);
    }
}
