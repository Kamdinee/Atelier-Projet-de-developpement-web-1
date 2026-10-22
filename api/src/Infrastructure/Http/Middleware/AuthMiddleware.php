<?php

declare(strict_types=1);

namespace Nrv\Infrastructure\Http\Middleware;

use Nrv\Infrastructure\Token\JwtTokenService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpUnauthorizedException;
use Throwable;

final class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly JwtTokenService $jwt,
    ) {
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        $authorization = $request->getHeaderLine('Authorization');

        if (!preg_match('/^Bearer\s+(\S+)$/i', $authorization, $matches)) {
            throw new HttpUnauthorizedException(
                $request,
                'Token d’authentification manquant.'
            );
        }

        try {
            $payload = $this->jwt->verify($matches[1]);
        } catch (Throwable $e) {
            throw new HttpUnauthorizedException(
                $request,
                'Token invalide ou expiré.',
                $e
            );
        }

        $userId = (int) ($payload['sub'] ?? 0);
        if ($userId <= 0) {
            throw new HttpUnauthorizedException(
                $request,
                'Identifiant utilisateur manquant dans le token.'
            );
        }

        $request = $request
            ->withAttribute('user_id', $userId)
            ->withAttribute('user_role', (string) ($payload['role'] ?? 'CLIENT'));

        return $handler->handle($request);
    }
}
