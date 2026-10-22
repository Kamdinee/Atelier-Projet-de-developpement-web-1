<?php

declare(strict_types=1);

namespace Nrv\Infrastructure\Http\Action;

use Nrv\Application\UseCase\ConsulterCommande;
use Nrv\Infrastructure\Http\Json;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

final class ConsulterCommandeAction
{
    public function __construct(
        private readonly ConsulterCommande $consulterCommande,
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
            throw new HttpUnauthorizedException($request, 'Authentification requise.');
        }

        $commandeId = $args['id'] ?? '';
        if ($commandeId === '') {
            throw new HttpBadRequestException($request, 'Identifiant de commande manquant.');
        }

        $userRole = (string) ($request->getAttribute('user_role') ?? 'CLIENT');

        try {
            $commande = $this->consulterCommande->executer($userId, $commandeId, $userRole);
        } catch (RuntimeException $e) {
            if (str_contains($e->getMessage(), "n'existe pas")) {
                throw new HttpNotFoundException($request, $e->getMessage());
            }
            if (str_contains($e->getMessage(), 'autorisé')) {
                throw new HttpForbiddenException($request, $e->getMessage());
            }
            throw new HttpBadRequestException($request, $e->getMessage());
        }

        return Json::reponse($response, [
            'commande' => $commande,
        ]);
    }
}
