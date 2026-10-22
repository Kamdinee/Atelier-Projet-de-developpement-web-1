<?php

declare(strict_types=1);

namespace Nrv\Infrastructure\Http\Action;

use Nrv\Application\UseCase\AuthentifierUtilisateur;
use Nrv\Infrastructure\Http\Json;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpUnauthorizedException;

final class AuthAction
{
    public function __construct(
        private readonly AuthentifierUtilisateur $authentifier,
    ) {
    }

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
    ): ResponseInterface {
        $data = $request->getParsedBody();

        if (!is_array($data)) {
            throw new HttpBadRequestException($request, 'Corps JSON invalide.');
        }

        $email = $data['email'] ?? null;
        $motDePasse = $data['mot_de_passe'] ?? null;

        if (!is_string($email) || !is_string($motDePasse)) {
            throw new HttpBadRequestException(
                $request,
                'Les champs email et mot_de_passe sont obligatoires.'
            );
        }

        try {
            $resultat = $this->authentifier->executer($email, $motDePasse);
        } catch (\RuntimeException $e) {
            throw new HttpUnauthorizedException($request, 'Identifiants invalides.');
        }

        return Json::reponse($response, $resultat);
    }
}
