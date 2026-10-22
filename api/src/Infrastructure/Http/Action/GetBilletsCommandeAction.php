<?php

declare(strict_types=1);

namespace Nrv\Infrastructure\Http\Action;

use Nrv\Application\Port\In\ConsulterMesAchatsInterface;
use Nrv\Infrastructure\Http\Json;
use Nrv\Infrastructure\Http\Presenter\BilletJson;
use Nrv\Infrastructure\Http\Validation\IdentifiantValidator;
use Nrv\Infrastructure\Security\UtilisateurConnecte;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// GET /commandes/{id}/billets : billets d'une commande de l'utilisateur connecte
final class GetBilletsCommandeAction
{
    public function __construct(
        private readonly ConsulterMesAchatsInterface $achats,
        private readonly IdentifiantValidator $validator,
    ) {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        /** @var UtilisateurConnecte $utilisateur */
        $utilisateur = $request->getAttribute(UtilisateurConnecte::ATTRIBUT);
        $commandeId = $this->validator->uuid($args['id'] ?? null);

        return Json::reponse($response, BilletJson::collection(
            $this->achats->billetsDeCommande($utilisateur->id, $commandeId),
        ))->withHeader('Cache-Control', 'private, no-store');
    }
}
