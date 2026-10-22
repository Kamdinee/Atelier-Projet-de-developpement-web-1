<?php

declare(strict_types=1);

namespace Nrv\Infrastructure\Http\Middleware;

use Nrv\Infrastructure\Http\Exception\NonAuthentifieException;
use Nrv\Infrastructure\Security\UtilisateurConnecte;
use Nrv\Infrastructure\Token\JwtService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

// route reservee aux utilisateurs connectes : en-tete Authorization: Bearer <jwt>
// l'utilisateur est pose sur la requete (attribut UtilisateurConnecte::ATTRIBUT) pour l'action
final class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly JwtService $jwt)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (preg_match('/^Bearer\s+(\S+)$/i', $request->getHeaderLine('Authorization'), $m) !== 1) {
            throw new NonAuthentifieException('connexion requise');
        }

        $utilisateur = $this->jwt->lire($m[1]);
        if ($utilisateur === null) {
            throw new NonAuthentifieException('session invalide ou expiree, reconnectez-vous');
        }

        return $handler->handle($request->withAttribute(UtilisateurConnecte::ATTRIBUT, $utilisateur));
    }
}
