<?php

declare(strict_types=1);

namespace Nrv\Tests\Infrastructure;

use Firebase\JWT\JWT;
use InvalidArgumentException;
use Nrv\Domain\ValueObject\Role;
use Nrv\Infrastructure\Token\JwtService;
use Nrv\Tests\Fixtures\Fabrique;
use PHPUnit\Framework\TestCase;

final class JwtServiceTest extends TestCase
{
    private const SECRET = 'secret-de-test-de-plus-de-32-caracteres';

    public function testUnJetonCreeSeRelitAvecLIdEtLeRole(): void
    {
        $jwt = new JwtService(self::SECRET);
        $porteur = $jwt->lire($jwt->creer(Fabrique::utilisateur(5, Role::Admin, 'admin@nrv.net')));

        $this->assertSame(5, $porteur?->id);
        $this->assertTrue($porteur->estAdmin());
    }

    public function testUnJetonModifieEstRefuse(): void
    {
        $jwt = new JwtService(self::SECRET);
        [$entete, , $signature] = explode('.', $jwt->creer(Fabrique::utilisateur(5)));
        // on essaie de se donner le role admin sans connaitre le secret
        $contenu = rtrim(strtr(base64_encode((string) json_encode(['iss' => 'nrv-api', 'sub' => '5', 'role' => 'ADMIN', 'exp' => time() + 60])), '+/', '-_'), '=');

        $this->assertNull($jwt->lire("{$entete}.{$contenu}.{$signature}"));
    }

    public function testUnJetonSigneAvecUnAutreSecretEstRefuse(): void
    {
        $autre = new JwtService('un-autre-secret-de-plus-de-32-caracteres');

        $this->assertNull((new JwtService(self::SECRET))->lire($autre->creer(Fabrique::utilisateur(5))));
    }

    public function testUnJetonExpireEstRefuse(): void
    {
        $perime = JWT::encode(['iss' => 'nrv-api', 'sub' => '5', 'role' => 'CLIENT', 'exp' => time() - 10], self::SECRET, 'HS256');

        $this->assertNull((new JwtService(self::SECRET))->lire($perime));
    }

    public function testUnJetonSansSignatureAlgNoneEstRefuse(): void
    {
        $b64 = static fn (array $d): string => rtrim(strtr(base64_encode((string) json_encode($d)), '+/', '-_'), '=');
        $jeton = $b64(['typ' => 'JWT', 'alg' => 'none']) . '.' . $b64(['iss' => 'nrv-api', 'sub' => '1', 'role' => 'ADMIN']) . '.';

        $this->assertNull((new JwtService(self::SECRET))->lire($jeton));
    }

    public function testUnSecretTropCourtEstRefuseAuDemarrage(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new JwtService('trop-court');
    }
}
