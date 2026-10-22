<?php

declare(strict_types=1);

namespace Nrv\Application\UseCase;

use Nrv\Application\Dto\SessionDto;
use Nrv\Application\Dto\UtilisateurDto;
use Nrv\Application\Port\In\AuthentifierUtilisateurInterface;
use Nrv\Application\Port\Out\HacheurMotDePasseInterface;
use Nrv\Application\Port\Out\JetonInterface;
use Nrv\Application\Port\Out\UtilisateurRepositoryInterface;
use Nrv\Domain\Exception\IdentifiantsInvalidesException;

final class AuthentifierUtilisateur implements AuthentifierUtilisateurInterface
{
    // hache sans compte : un email inconnu prend autant de temps qu'un mauvais mot de passe
    private const HACHE_FICTIF = '$2y$12$NjBNMdNa9sqd6Q4AVHt7GuUPiqzmVPAo9ht1MrIbVijCDcCYJnL0i';

    public function __construct(
        private readonly UtilisateurRepositoryInterface $utilisateurs,
        private readonly HacheurMotDePasseInterface $hacheur,
        private readonly JetonInterface $jetons,
    ) {
    }

    public function executer(string $email, string $motDePasse): SessionDto
    {
        $utilisateur = $this->utilisateurs->trouverParEmail(mb_strtolower(trim($email)));
        $valide = $this->hacheur->verifier($motDePasse, $utilisateur?->motDePasseHache() ?? self::HACHE_FICTIF);

        if ($utilisateur === null || !$valide) {
            throw new IdentifiantsInvalidesException();
        }

        return new SessionDto(
            $this->jetons->creer($utilisateur),
            $this->jetons->expiration(),
            UtilisateurDto::depuis($utilisateur),
        );
    }
}
