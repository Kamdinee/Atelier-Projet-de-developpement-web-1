<?php

declare(strict_types=1);

namespace Nrv\Infrastructure\Http\Action;

use InvalidArgumentException;
use Nrv\Application\UseCase\PayerCommande;
use Nrv\Domain\Exception\PlacesInsuffisantesException;
use Nrv\Infrastructure\Http\Json;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

final class PayerCommandeAction
{
    public function __construct(
        private readonly PayerCommande $payerCommande,
    ) {
    }

    /**
     * @param array<string, string> $args
     */
    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args,
    ): ResponseInterface {
        $userId = (int) $request->getAttribute('user_id');
        if ($userId <= 0) {
            throw new HttpUnauthorizedException($request, 'Authentification requise pour effectuer un paiement.');
        }

        $commandeId = $args['id'] ?? '';
        if ($commandeId === '') {
            throw new HttpBadRequestException($request, 'Identifiant de commande manquant.');
        }

        $data = $request->getParsedBody();
        if (!is_array($data)) {
            throw new HttpBadRequestException($request, 'Données de paiement manquantes ou invalides.');
        }

        try {
            $commande = $this->payerCommande->executer($userId, $commandeId, $data);
        } catch (InvalidArgumentException $e) {
            throw new HttpBadRequestException($request, $e->getMessage());
        } catch (PlacesInsuffisantesException $e) {
            throw new HttpException($request, $e->getMessage(), 409);
        } catch (RuntimeException $e) {
            if (str_contains($e->getMessage(), "n'existe pas")) {
                throw new HttpNotFoundException($request, $e->getMessage());
            }
            if (str_contains($e->getMessage(), 'autorisé')) {
                throw new HttpForbiddenException($request, $e->getMessage());
            }
            throw new HttpException($request, $e->getMessage(), 422);
        }

        return Json::reponse($response, [
            'message' => 'Paiement validé avec succès. Vos billets sont disponibles !',
            'commande' => $commande,
        ]);
    }
}
