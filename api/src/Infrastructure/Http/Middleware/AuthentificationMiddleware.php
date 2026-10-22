<?php

declare(strict_types=1);

namespace Nrv\Infrastructure\Http\Middleware;

use Nrv\Infrastructure\Http\Exception\NonAuthentifieException;
use Nrv\Infrastructure\Security\JwtDecodeur;
use Nrv\Infrastructure\Security\UtilisateurConnecte;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

// route reservee aux utilisateurs connectes : lit "Authorization: Bearer <jwt>"
// et pose l'utilisateur sur la requete (attribut UtilisateurConnecte::ATTRIBUT)
final class AuthentificationMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly JwtDecodeur $decodeur)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $entete = $request->getHeaderLine('Authorization');
        if (preg_match('/^Bearer\s+(\S+)$/i', $entete, $morceaux) !== 1) {
            throw new NonAuthentifieException('connexion requise');
        }

        $utilisateur = $this->decodeur->decoder($morceaux[1]);
        if ($utilisateur === null) {
            throw new NonAuthentifieException('session invalide ou expiree, reconnectez-vous');
        }

        return $handler->handle($request->withAttribute(UtilisateurConnecte::ATTRIBUT, $utilisateur));
    }
}
