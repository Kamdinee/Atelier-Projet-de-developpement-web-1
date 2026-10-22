<?php

declare(strict_types=1);

namespace Nrv\Infrastructure\Persistence\Pdo;

use Nrv\Application\Port\Out\UtilisateurRepositoryInterface;
use PDO;

final class PDOUtilisateurRepository implements 
UtilisateurRepositoryInterface
{
    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    public function trouverParEmail(string $email): ?array
    {
        $sql = <<<'SQL'
            SELECT id, email, nom, prenom, mot_de_passe, role
            FROM utilisateurs
            WHERE email = :email
            LIMIT 1
            SQL;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['email' => $email]);

        $utilisateur = $stmt->fetch();

        return $utilisateur === false ? null : $utilisateur;
    }

    public function creer(
        string $email,
        string $nom,
        string $prenom,
        string $motDePasseHache,
    ): int {
        $sql = <<<'SQL'
            INSERT INTO utilisateurs (email, nom, prenom, mot_de_passe, role)
            VALUES (:email, :nom, :prenom, :mot_de_passe, 'CLIENT')
            SQL;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'email' => $email,
            'nom' => $nom,
            'prenom' => $prenom,
            'mot_de_passe' => $motDePasseHache,
        ]);

        return (int) $this->pdo->lastInsertId();
    }
}
