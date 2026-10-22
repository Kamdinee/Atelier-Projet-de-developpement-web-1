<?php

declare(strict_types=1);

namespace Nrv\Infrastructure\Http\Middleware;

use Nrv\Infrastructure\Token\JwtService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpUnauthorizedException;

// route reservee aux utilisateurs connectes : en-tete Authorization: Bearer <jwt>
// l'id et le role du porteur sont passes a l'action dans l'attribut "utilisateur"
final class AuthMiddleware implements MiddlewareInterface
{
    public const ATTRIBUT = 'utilisateur';

    public function __construct(private readonly JwtService $jwt)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (preg_match('/^Bearer\s+(\S+)$/i', $request->getHeaderLine('Authorization'), $m) !== 1) {
            throw new HttpUnauthorizedException($request, 'connexion requise');
        }

        $porteur = $this->jwt->lire($m[1]);
        if ($porteur === null) {
            throw new HttpUnauthorizedException($request, 'jeton invalide ou expire, reconnectez-vous');
        }

        return $handler->handle($request->withAttribute(self::ATTRIBUT, $porteur));
    }
}
