<?php

declare(strict_types=1);

namespace Nrv\Infrastructure\Http\Action;

use Nrv\Application\Dto\CommandeDto;
use Nrv\Application\Port\In\ConsulterMesAchatsInterface;
use Nrv\Infrastructure\Http\Json;
use Nrv\Infrastructure\Http\Lien;
use Nrv\Infrastructure\Security\UtilisateurConnecte;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// GET /commandes : les commandes de l'utilisateur connecte
final class GetMesCommandesAction
{
    public function __construct(private readonly ConsulterMesAchatsInterface $achats)
    {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        /** @var UtilisateurConnecte $utilisateur */
        $utilisateur = $request->getAttribute(UtilisateurConnecte::ATTRIBUT);
        $commandes = $this->achats->commandes($utilisateur->id);

        return Json::reponse($response, [
            'type' => 'collection',
            'count' => count($commandes),
            'commandes' => array_map(static fn (CommandeDto $c): array => [
                'commande' => [
                    'id' => $c->id,
                    'statut' => $c->statut,
                    'total' => $c->total,
                    'nb_billets' => $c->nombreBillets,
                    'cree_le' => $c->creeLe,
                    'payee_le' => $c->payeeLe,
                ],
                'links' => ['billets' => Lien::billetsCommande($c->id)],
            ], $commandes),
            'links' => ['billets' => ['href' => '/billets']],
        ])->withHeader('Cache-Control', 'private, no-store');
    }
}
