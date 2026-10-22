<?php

declare(strict_types=1);

namespace Nrv\Application\UseCase;

use Nrv\Application\Port\Out\UtilisateurRepositoryInterface;
use Nrv\Infrastructure\Security\PasswordHasher;
use RuntimeException;
use PDOException;

final class InscrireUtilisateur
{
    public function __construct(
        private readonly UtilisateurRepositoryInterface $utilisateurs,
        private readonly PasswordHasher $passwordHasher,
    ) {
    }

    public function executer(
        string $email,
        string $nom,
        string $prenom,
        string $motDePasse,
    ): array {
        $email = trim($email);
        $nom = trim($nom);
        $prenom = trim($prenom);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Adresse email invalide.');
        }

        if ($nom === '' || $prenom === '') {
            throw new RuntimeException('Le nom et le prénom sont obligatoires.');
        }

        if (strlen($motDePasse) < 8) {
            throw new RuntimeException('Le mot de passe doit contenir au moins 8 caractères.');
        }

        if ($this->utilisateurs->trouverParEmail($email) !== null) {
            throw new RuntimeException('Cette adresse email est déjà utilisée.');
        }

        $motDePasseHache = $this->passwordHasher->hash($motDePasse);

        try {
            $id = $this->utilisateurs->creer(
                $email,
                $nom,
                $prenom,
                $motDePasseHache,
            );
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                throw new RuntimeException('Cette adresse email est déjà utilisée.');
            }

            throw $e;
        }

        return [
            'id' => $id,
            'email' => $email,
            'nom' => $nom,
            'prenom' => $prenom,
            'role' => 'CLIENT',
        ];
    }
}
