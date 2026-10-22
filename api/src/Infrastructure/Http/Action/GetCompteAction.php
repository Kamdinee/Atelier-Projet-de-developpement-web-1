<?php

declare(strict_types=1);

namespace Nrv\Infrastructure\Http\Action;

use Nrv\Application\Port\In\ConsulterCompteInterface;
use Nrv\Infrastructure\Http\Json;
use Nrv\Infrastructure\Http\Lien;
use Nrv\Infrastructure\Http\Presenter\UtilisateurJson;
use Nrv\Infrastructure\Security\UtilisateurConnecte;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// GET /compte : le compte de l'utilisateur connecte (route protegee par AuthMiddleware)
final class GetCompteAction
{
    public function __construct(private readonly ConsulterCompteInterface $consulterCompte)
    {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        /** @var UtilisateurConnecte $utilisateur */
        $utilisateur = $request->getAttribute(UtilisateurConnecte::ATTRIBUT);
        $compte = $this->consulterCompte->executer($utilisateur->id);

        return Json::reponse($response, [
            'type' => 'resource',
            'utilisateur' => UtilisateurJson::compte($compte),
            'links' => ['self' => Lien::compte()],
        ]);
    }
}
