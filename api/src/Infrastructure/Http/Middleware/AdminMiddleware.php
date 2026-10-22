<?php

declare(strict_types=1);

namespace Nrv\Infrastructure\Http\Middleware;

use Nrv\Infrastructure\Token\JwtTokenService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpUnauthorizedException;
use Throwable;

final class AdminMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly JwtTokenService $jwt,
        private readonly ResponseFactoryInterface $responseFactory,
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

        if (($payload['role'] ?? null) !== 'admin') {
            throw new HttpForbiddenException(
                $request,
                'Accès réservé aux administrateurs.'
            );
        }

        return $handler->handle($request);
    }
}
