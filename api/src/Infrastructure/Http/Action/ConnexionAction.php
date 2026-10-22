<?php

declare(strict_types=1);

namespace Nrv\Infrastructure\Http\Action;

use Nrv\Application\Port\In\AuthentifierUtilisateurInterface;
use Nrv\Infrastructure\Http\Json;
use Nrv\Infrastructure\Http\Lien;
use Nrv\Infrastructure\Http\Presenter\UtilisateurJson;
use Nrv\Infrastructure\Http\Validation\CompteValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// POST /auth/signin : renvoie un jeton jwt a envoyer ensuite dans l'en-tete Authorization
final class ConnexionAction
{
    public function __construct(
        private readonly AuthentifierUtilisateurInterface $authentifier,
        private readonly CompteValidator $validator,
    ) {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $d = $this->validator->connexion($request->getParsedBody());
        $session = $this->authentifier->executer($d['email'], $d['mot_de_passe']);

        return Json::reponse($response, [
            'type' => 'resource',
            'token' => $session->jeton,
            'expire_le' => $session->expireLe,
            'utilisateur' => UtilisateurJson::compte($session->utilisateur),
            'links' => ['compte' => Lien::compte()],
        ]);
    }
}
