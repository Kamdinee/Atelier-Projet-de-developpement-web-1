<?php

declare(strict_types=1);

namespace Nrv\Tests\Infrastructure;

use Nrv\Domain\ValueObject\Role;
use Nrv\Infrastructure\Http\Middleware\AdminMiddleware;
use Nrv\Infrastructure\Http\Middleware\AuthMiddleware;
use Nrv\Infrastructure\Token\JwtService;
use Nrv\Tests\Fixtures\Fabrique;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response;

final class MiddlewaresTest extends TestCase
{
    private JwtService $jwt;

    protected function setUp(): void
    {
        $this->jwt = new JwtService('secret-de-test-de-plus-de-32-caracteres');
    }

    private function requete(?string $authorization = null): ServerRequestInterface
    {
        $requete = (new ServerRequestFactory())->createServerRequest('GET', '/compte');

        return $authorization === null ? $requete : $requete->withHeader('Authorization', $authorization);
    }

    // action factice : garde la requete recue pour verifier l'attribut
    private function action(): RequestHandlerInterface
    {
        return new class () implements RequestHandlerInterface {
            public ?ServerRequestInterface $recue = null;

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->recue = $request;

                return new Response(200);
            }
        };
    }

    public function testSansJetonLaRouteRepond401(): void
    {
        $this->expectException(HttpUnauthorizedException::class);

        (new AuthMiddleware($this->jwt))->process($this->requete(), $this->action());
    }

    public function testUnEnTeteMalFormeRepond401(): void
    {
        $this->expectException(HttpUnauthorizedException::class);

        (new AuthMiddleware($this->jwt))->process($this->requete('Basic Y2xpZW50'), $this->action());
    }

    public function testUnJetonValidePasseLIdALAction(): void
    {
        $action = $this->action();
        $jeton = $this->jwt->creer(Fabrique::utilisateur(3));

        (new AuthMiddleware($this->jwt))->process($this->requete("Bearer {$jeton}"), $action);

        $this->assertSame(['id' => 3, 'role' => Role::Client], $action->recue?->getAttribute(AuthMiddleware::ATTRIBUT));
    }

    public function testUnClientNAccedePasAuBackoffice(): void
    {
        $requete = $this->requete()->withAttribute(AuthMiddleware::ATTRIBUT, ['id' => 3, 'role' => Role::Client]);

        $this->expectException(HttpForbiddenException::class);

        (new AdminMiddleware())->process($requete, $this->action());
    }

    public function testUnAdministrateurAccedeAuBackoffice(): void
    {
        $requete = $this->requete()->withAttribute(AuthMiddleware::ATTRIBUT, ['id' => 1, 'role' => Role::Admin]);

        $this->assertSame(200, (new AdminMiddleware())->process($requete, $this->action())->getStatusCode());
    }

    public function testLeBackofficeSansConnexionRepond401(): void
    {
        $this->expectException(HttpUnauthorizedException::class);

        (new AdminMiddleware())->process($this->requete(), $this->action());
    }
}
