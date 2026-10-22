<?php

declare(strict_types=1);

namespace Nrv\Infrastructure\Token;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use InvalidArgumentException;
use Nrv\Application\Port\Out\JetonInterface;
use Nrv\Domain\Entity\Utilisateur;
use Nrv\Domain\ValueObject\Role;
use Throwable;

// adaptateur du port JetonInterface : jwt signe en hs256 avec le secret du .env
final class JwtService implements JetonInterface
{
    private const ALGO = 'HS256';
    private const EMETTEUR = 'nrv-api';

    public function __construct(
        private readonly string $secret,
        private readonly int $dureeSecondes = 3600,
    ) {
        if (strlen($secret) < 32) {
            throw new InvalidArgumentException('JWT_SECRET doit faire au moins 32 caracteres');
        }
    }

    public function creer(Utilisateur $utilisateur): string
    {
        $maintenant = time();

        return JWT::encode([
            'iss' => self::EMETTEUR,
            'iat' => $maintenant,
            'exp' => $maintenant + $this->dureeSecondes,
            'sub' => (string) $utilisateur->id(),
            'role' => $utilisateur->role()->value,
        ], $this->secret, self::ALGO);
    }

    public function expiration(): string
    {
        return date(DATE_ATOM, time() + $this->dureeSecondes);
    }

    // id et role du porteur, ou null si le jeton est faux, expire ou d'un autre emetteur
    /** @return array{id: int, role: Role}|null */
    public function lire(string $jeton): ?array
    {
        try {
            $contenu = (array) JWT::decode($jeton, new Key($this->secret, self::ALGO));
        } catch (Throwable) {
            return null;
        }

        $id = filter_var($contenu['sub'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $role = Role::tryFrom((string) ($contenu['role'] ?? ''));
        if (($contenu['iss'] ?? null) !== self::EMETTEUR || $id === false || $role === null) {
            return null;
        }

        return ['id' => $id, 'role' => $role];
    }
}
