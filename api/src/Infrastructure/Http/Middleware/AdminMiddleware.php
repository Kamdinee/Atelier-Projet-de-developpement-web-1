<?php

declare(strict_types=1);

namespace Nrv\Infrastructure\Http\Middleware;

use Nrv\Domain\ValueObject\Role;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpUnauthorizedException;

// route reservee aux administrateurs (backoffice) ; se place apres AuthMiddleware :
// $app->group('/admin', ...)->add(AdminMiddleware::class)->add(AuthMiddleware::class);
final class AdminMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $porteur = $request->getAttribute(AuthMiddleware::ATTRIBUT);
        if (!is_array($porteur)) {
            throw new HttpUnauthorizedException($request, 'connexion requise');
        }
        if ($porteur['role'] !== Role::Admin) {
            throw new HttpForbiddenException($request, 'acces reserve aux administrateurs');
        }

        return $handler->handle($request);
    }
}
