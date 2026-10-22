<?php

declare(strict_types=1);

namespace Nrv\Infrastructure\Http\Action;

use InvalidArgumentException;
use Nrv\Application\UseCase\CreerCommande;
use Nrv\Domain\Exception\PlacesInsuffisantesException;
use Nrv\Infrastructure\Http\Json;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpConflictException;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpUnprocessableEntityException;

final class CreerCommandeAction
{
    public function __construct(
        private readonly CreerCommande $creerCommande,
    ) {
    }

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
    ): ResponseInterface {
        $userId = (int) $request->getAttribute('user_id');
        if ($userId <= 0) {
            throw new HttpUnauthorizedException($request, 'Authentification requise pour commander.');
        }

        $data = $request->getParsedBody();
        if (!is_array($data)) {
            throw new HttpBadRequestException($request, 'Corps de requête JSON invalide.');
        }

        // Accepte { "lignes": [...] } ou directement [...]
        $lignes = isset($data['lignes']) && is_array($data['lignes']) ? $data['lignes'] : $data;

        if (empty($lignes)) {
            throw new HttpBadRequestException($request, 'Le panier est vide ou ne contient aucune ligne.');
        }

        try {
            $commande = $this->creerCommande->executer($userId, $lignes);
        } catch (InvalidArgumentException $e) {
            throw new HttpBadRequestException($request, $e->getMessage());
        } catch (PlacesInsuffisantesException $e) {
            throw new HttpConflictException($request, $e->getMessage());
        } catch (RuntimeException $e) {
            throw new HttpUnprocessableEntityException($request, $e->getMessage());
        }

        return Json::reponse($response, [
            'message' => 'Commande créée avec succès.',
            'commande' => $commande,
        ], 201);
    }
}
