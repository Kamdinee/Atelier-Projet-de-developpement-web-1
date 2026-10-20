<?php

declare(strict_types=1);

namespace Nrv\Infrastructure\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

// ajoute les entetes cors pour les origines autorisees, repond 204 aux preflight
final class CorsMiddleware implements MiddlewareInterface
{
    /**
     * @param list<string> $originesAutorisees ex. ['http://localhost:8081'], ou ['*'] pour tout accepter
     */
    public function __construct(private readonly array $originesAutorisees)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $request->getMethod() === 'OPTIONS'
            ? (new Response())->withStatus(204)
            : $handler->handle($request);

        $origine = $request->getHeaderLine('Origin');
        if ($origine === '' || !$this->estAutorisee($origine)) {
            return $response->withAddedHeader('Vary', 'Origin');
        }

        return $response
            ->withHeader('Access-Control-Allow-Origin', $origine)
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PATCH, OPTIONS')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')
            ->withHeader('Access-Control-Expose-Headers', 'Location')
            ->withHeader('Access-Control-Max-Age', '3600')
            ->withAddedHeader('Vary', 'Origin');
    }

    private function estAutorisee(string $origine): bool
    {
        return in_array('*', $this->originesAutorisees, true)
            || in_array($origine, $this->originesAutorisees, true);
    }
}
