<?php

declare(strict_types=1);

namespace Nrv\Infrastructure\Http\Action;

use Nrv\Application\Port\In\ConsulterCommandeInterface;
use Nrv\Infrastructure\Http\Json;
use Nrv\Infrastructure\Http\Presenter\CommandeJson;
use Nrv\Infrastructure\Http\Validation\IdentifiantValidator;
use Nrv\Infrastructure\Security\UtilisateurConnecte;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// GET /commandes/{id} : une commande de l'utilisateur connecte (recapitulatif avant paiement)
final class GetCommandeAction
{
    public function __construct(
        private readonly ConsulterCommandeInterface $consulterCommande,
        private readonly IdentifiantValidator $validator,
    ) {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        /** @var UtilisateurConnecte $utilisateur */
        $utilisateur = $request->getAttribute(UtilisateurConnecte::ATTRIBUT);
        $commande = $this->consulterCommande->executer($utilisateur->id, $this->validator->uuid($args['id'] ?? null));

        return Json::reponse($response, ['type' => 'resource'] + CommandeJson::detail($commande))
            ->withHeader('Cache-Control', 'private, no-store');
    }
}
