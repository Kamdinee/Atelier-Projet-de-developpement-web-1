<?php

declare(strict_types=1);

namespace Nrv\Application\UseCase;

use Nrv\Application\Dto\UtilisateurDto;
use Nrv\Application\Port\In\InscrireUtilisateurInterface;
use Nrv\Application\Port\Out\HacheurMotDePasseInterface;
use Nrv\Application\Port\Out\UtilisateurRepositoryInterface;
use Nrv\Domain\Entity\Utilisateur;
use Nrv\Domain\Exception\DonneeInvalideException;
use Nrv\Domain\Exception\EmailDejaUtiliseException;
use Nrv\Domain\ValueObject\Role;

final class InscrireUtilisateur implements InscrireUtilisateurInterface
{
    public const MOT_DE_PASSE_MIN = 8;
    // bcrypt ne tient compte que des 72 premiers octets
    public const MOT_DE_PASSE_MAX = 72;

    public function __construct(
        private readonly UtilisateurRepositoryInterface $utilisateurs,
        private readonly HacheurMotDePasseInterface $hacheur,
    ) {
    }

    public function executer(string $email, string $nom, string $prenom, string $motDePasse): UtilisateurDto
    {
        $longueur = strlen($motDePasse);
        if ($longueur < self::MOT_DE_PASSE_MIN || $longueur > self::MOT_DE_PASSE_MAX) {
            throw new DonneeInvalideException(
                'le mot de passe fait de ' . self::MOT_DE_PASSE_MIN . ' a ' . self::MOT_DE_PASSE_MAX . ' caracteres'
            );
        }

        // l'entite verifie l'email, le nom et le prenom ; un compte cree ici est toujours client
        $utilisateur = new Utilisateur(null, $email, $nom, $prenom, $this->hacheur->hacher($motDePasse), Role::Client);

        if ($this->utilisateurs->trouverParEmail($utilisateur->email()) !== null) {
            throw new EmailDejaUtiliseException();
        }

        return UtilisateurDto::depuis($this->utilisateurs->ajouter($utilisateur));
    }
}
