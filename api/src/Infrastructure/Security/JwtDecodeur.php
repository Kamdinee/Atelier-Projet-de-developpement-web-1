<?php

declare(strict_types=1);

namespace Nrv\Infrastructure\Security;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Throwable;

// verifie un jeton jwt (HS256, secret JWT_SECRET) et renvoie l'utilisateur qu'il porte
// claims attendus : sub = id de l'utilisateur, role = CLIENT | ADMIN, email (facultatif)
// a aligner sur le jeton emis par /auth/signin (etape 5) a la fusion
final class JwtDecodeur
{
    public const ALGORITHME = 'HS256';

    public function __construct(private readonly string $secret)
    {
    }

    // null si le jeton est invalide, expire ou incomplet
    public function decoder(string $jeton): ?UtilisateurConnecte
    {
        try {
            $claims = (array) JWT::decode($jeton, new Key($this->secret, self::ALGORITHME));
        } catch (Throwable) {
            return null;
        }

        $id = filter_var($claims['sub'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $role = $claims['role'] ?? null;
        if ($id === false || !in_array($role, ['CLIENT', 'ADMIN'], true)) {
            return null;
        }

        return new UtilisateurConnecte($id, $role, isset($claims['email']) ? (string) $claims['email'] : null);
    }
}
