<?php

declare(strict_types=1);

namespace Nrv\Infrastructure\Http\Middleware;

use Nrv\Infrastructure\Http\Exception\AccesInterditException;
use Nrv\Infrastructure\Http\Exception\NonAuthentifieException;
use Nrv\Infrastructure\Security\UtilisateurConnecte;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

// route du backoffice : reservee au role ADMIN, se place apres AuthMiddleware
final class AdminMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $utilisateur = $request->getAttribute(UtilisateurConnecte::ATTRIBUT);
        if (!$utilisateur instanceof UtilisateurConnecte) {
            throw new NonAuthentifieException('connexion requise');
        }
        if (!$utilisateur->estAdmin()) {
            throw new AccesInterditException('reserve aux administrateurs');
        }

        return $handler->handle($request);
    }
}
