<?php

declare(strict_types=1);

namespace Nrv\Infrastructure\Http\Action;

use Nrv\Application\Port\In\CreerCommandeInterface;
use Nrv\Infrastructure\Http\Json;
use Nrv\Infrastructure\Http\Lien;
use Nrv\Infrastructure\Http\Presenter\CommandeJson;
use Nrv\Infrastructure\Http\Validation\CommandeValidator;
use Nrv\Infrastructure\Security\UtilisateurConnecte;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// POST /commandes : le panier devient une commande "en attente" (F8) ; 201 + lien vers le paiement
final class PostCommandeAction
{
    public function __construct(
        private readonly CreerCommandeInterface $creerCommande,
        private readonly CommandeValidator $validator,
    ) {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        /** @var UtilisateurConnecte $utilisateur */
        $utilisateur = $request->getAttribute(UtilisateurConnecte::ATTRIBUT);
        $commande = $this->creerCommande->executer($utilisateur->id, $this->validator->commande($request->getParsedBody()));

        return Json::reponse($response, ['type' => 'resource'] + CommandeJson::detail($commande), 201)
            ->withHeader('Location', Lien::commande($commande->id)['href']);
    }
}
