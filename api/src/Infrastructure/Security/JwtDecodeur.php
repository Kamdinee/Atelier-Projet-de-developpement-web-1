<?php

declare(strict_types=1);

namespace Nrv\Infrastructure\Security;

use Nrv\Infrastructure\Token\JwtTokenService;
use Throwable;

// lit l'utilisateur porte par un jeton jwt emis a la connexion (POST /auth)
// claims utilises : sub = id de l'utilisateur, role = CLIENT | ADMIN
final class JwtDecodeur
{
    public function __construct(private readonly JwtTokenService $jwt)
    {
    }

    // null si le jeton est invalide, expire ou incomplet
    public function decoder(string $jeton): ?UtilisateurConnecte
    {
        try {
            $claims = $this->jwt->verify($jeton);
        } catch (Throwable) {
            return null;
        }

        $id = filter_var($claims['sub'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $role = strtoupper((string) ($claims['role'] ?? ''));
        if ($id === false || !in_array($role, ['CLIENT', 'ADMIN'], true)) {
            return null;
        }

        return new UtilisateurConnecte($id, $role, isset($claims['email']) ? (string) $claims['email'] : null);
    }
}
