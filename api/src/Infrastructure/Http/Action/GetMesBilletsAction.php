<?php

declare(strict_types=1);

namespace Nrv\Infrastructure\Http\Action;

use Nrv\Application\Port\In\ConsulterMesAchatsInterface;
use Nrv\Infrastructure\Http\Json;
use Nrv\Infrastructure\Http\Presenter\BilletJson;
use Nrv\Infrastructure\Security\UtilisateurConnecte;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// GET /billets : tous les billets de l'utilisateur connecte
final class GetMesBilletsAction
{
    public function __construct(private readonly ConsulterMesAchatsInterface $achats)
    {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        /** @var UtilisateurConnecte $utilisateur */
        $utilisateur = $request->getAttribute(UtilisateurConnecte::ATTRIBUT);

        return Json::reponse($response, BilletJson::collection($this->achats->billets($utilisateur->id)))
            ->withHeader('Cache-Control', 'private, no-store');
    }
}
