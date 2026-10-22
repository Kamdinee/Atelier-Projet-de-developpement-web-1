<?php

declare(strict_types=1);

namespace Nrv\Infrastructure\Persistence\Pdo;

use Nrv\Application\Port\Out\UtilisateurRepositoryInterface;
use Nrv\Domain\Entity\Utilisateur;
use Nrv\Domain\Exception\EmailDejaUtiliseException;
use Nrv\Domain\ValueObject\Role;
use PDO;
use PDOException;

// adaptateur pdo du port UtilisateurRepositoryInterface
final class PDOUtilisateurRepository implements UtilisateurRepositoryInterface
{
    private const SELECT = 'SELECT id, email, nom, prenom, mot_de_passe, role FROM utilisateurs';

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function trouverParEmail(string $email): ?Utilisateur
    {
        $requete = $this->pdo->prepare(self::SELECT . ' WHERE email = :email');
        $requete->bindValue('email', $email, PDO::PARAM_STR);
        $requete->execute();

        return $this->hydrater($requete->fetch(PDO::FETCH_ASSOC));
    }

    public function trouverParId(int $id): ?Utilisateur
    {
        $requete = $this->pdo->prepare(self::SELECT . ' WHERE id = :id');
        $requete->bindValue('id', $id, PDO::PARAM_INT);
        $requete->execute();

        return $this->hydrater($requete->fetch(PDO::FETCH_ASSOC));
    }

    public function ajouter(Utilisateur $utilisateur): Utilisateur
    {
        $requete = $this->pdo->prepare(
            'INSERT INTO utilisateurs (email, nom, prenom, mot_de_passe, role)
            VALUES (:email, :nom, :prenom, :mot_de_passe, :role)'
        );
        $requete->bindValue('email', $utilisateur->email(), PDO::PARAM_STR);
        $requete->bindValue('nom', $utilisateur->nom(), PDO::PARAM_STR);
        $requete->bindValue('prenom', $utilisateur->prenom(), PDO::PARAM_STR);
        $requete->bindValue('mot_de_passe', $utilisateur->motDePasseHache(), PDO::PARAM_STR);
        $requete->bindValue('role', $utilisateur->role()->value, PDO::PARAM_STR);

        try {
            $requete->execute();
        } catch (PDOException $e) {
            // contrainte uq_utilisateurs_email : deux inscriptions simultanees avec le meme email
            if ($e->getCode() === '23000') {
                throw new EmailDejaUtiliseException();
            }
            throw $e;
        }

        return $utilisateur->avecId((int) $this->pdo->lastInsertId());
    }

    private function hydrater(array|false $ligne): ?Utilisateur
    {
        if ($ligne === false) {
            return null;
        }

        return new Utilisateur(
            (int) $ligne['id'],
            (string) $ligne['email'],
            (string) $ligne['nom'],
            (string) $ligne['prenom'],
            (string) $ligne['mot_de_passe'],
            Role::from((string) $ligne['role']),
        );
    }
}
