<?php

declare(strict_types=1);

namespace Nrv\Application\UseCase;

use Nrv\Application\Port\Out\UtilisateurRepositoryInterface;
use Nrv\Infrastructure\Security\PasswordHasher;
use Nrv\Infrastructure\Token\JwtTokenService;
use RuntimeException;

final class AuthentifierUtilisateur
{
    public function __construct(
        private readonly UtilisateurRepositoryInterface $utilisateurs,
        private readonly PasswordHasher $passwordHasher,
        private readonly JwtTokenService $jwt,
    ) {
    }

    public function executer(string $email, string $motDePasse): array
    {
        $email = trim($email);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $motDePasse === '') {
            throw new RuntimeException('Identifiants invalides.');
        }

        $utilisateur = $this->utilisateurs->trouverParEmail($email);

        if (
            $utilisateur === null
            || ! $this->passwordHasher->verify(
                $motDePasse,
                (string) $utilisateur['mot_de_passe']
            )
        ) {
            throw new RuntimeException('Identifiants invalides.');
        }

        $token = $this->jwt->generate(
            (int) $utilisateur['id'],
            (string) $utilisateur['role']
        );

        return [
            'token' => $token,
            'utilisateur' => [
                'id' => (int) $utilisateur['id'],
                'email' => (string) $utilisateur['email'],
                'nom' => (string) $utilisateur['nom'],
                'prenom' => (string) $utilisateur['prenom'],
                'role' => (string) $utilisateur['role'],
            ],
        ];
    }
}
