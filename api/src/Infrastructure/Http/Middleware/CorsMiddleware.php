<?php

declare(strict_types=1);

namespace Nrv\Infrastructure\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

// ajoute les entetes cors, repond 204 aux preflight
final class CorsMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly string $origine)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $request->getMethod() === 'OPTIONS'
            ? (new Response())->withStatus(204)
            : $handler->handle($request);

        return $response
            ->withHeader('Access-Control-Allow-Origin', $this->origine)
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PATCH, OPTIONS')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')
            ->withHeader('Access-Control-Expose-Headers', 'Location')
            ->withHeader('Access-Control-Max-Age', '3600')
            ->withHeader('Vary', 'Origin');
    }
}
