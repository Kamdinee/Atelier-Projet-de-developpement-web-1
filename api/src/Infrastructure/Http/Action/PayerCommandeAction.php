<?php

declare(strict_types=1);

namespace Nrv\Infrastructure\Http\Action;

use Nrv\Application\Port\In\PayerCommandeInterface;
use Nrv\Infrastructure\Http\Json;
use Nrv\Infrastructure\Http\Presenter\BilletJson;
use Nrv\Infrastructure\Http\Presenter\CommandeJson;
use Nrv\Infrastructure\Http\Validation\CommandeValidator;
use Nrv\Infrastructure\Http\Validation\IdentifiantValidator;
use Nrv\Infrastructure\Security\UtilisateurConnecte;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// POST /commandes/{id}/paiement : paiement simule (F9), places reservees et billets emis
// 402 carte refusee, 409 deja payee ou places insuffisantes, 422 carte expiree ou numero faux
final class PayerCommandeAction
{
    public function __construct(
        private readonly PayerCommandeInterface $payerCommande,
        private readonly CommandeValidator $validator,
        private readonly IdentifiantValidator $identifiants,
    ) {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        /** @var UtilisateurConnecte $utilisateur */
        $utilisateur = $request->getAttribute(UtilisateurConnecte::ATTRIBUT);
        $commandeId = $this->identifiants->uuid($args['id'] ?? null);
        $paiement = $this->payerCommande->executer($utilisateur->id, $commandeId, $this->validator->carte($request->getParsedBody()));

        return Json::reponse($response, [
            'type' => 'resource',
            ...CommandeJson::detail($paiement->commande),
            'billets' => BilletJson::collection($paiement->billets)['billets'],
        ])->withHeader('Cache-Control', 'private, no-store');
    }
}
