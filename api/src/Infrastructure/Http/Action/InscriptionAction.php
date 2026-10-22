<?php

declare(strict_types=1);

namespace Nrv\Infrastructure\Http\Action;

use Nrv\Application\Port\In\AuthentifierUtilisateurInterface;
use Nrv\Application\Port\In\InscrireUtilisateurInterface;
use Nrv\Infrastructure\Http\Json;
use Nrv\Infrastructure\Http\Lien;
use Nrv\Infrastructure\Http\Presenter\UtilisateurJson;
use Nrv\Infrastructure\Http\Validation\CompteValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// POST /auth/register : cree un compte client et connecte directement l'utilisateur (201)
final class InscriptionAction
{
    public function __construct(
        private readonly InscrireUtilisateurInterface $inscrire,
        private readonly AuthentifierUtilisateurInterface $authentifier,
        private readonly CompteValidator $validator,
    ) {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $d = $this->validator->inscription($request->getParsedBody());

        $this->inscrire->executer($d['email'], $d['nom'], $d['prenom'], $d['mot_de_passe']);
        $session = $this->authentifier->executer($d['email'], $d['mot_de_passe']);

        return Json::reponse($response, [
            'type' => 'resource',
            'token' => $session->jeton,
            'expire_le' => $session->expireLe,
            'utilisateur' => UtilisateurJson::compte($session->utilisateur),
            'links' => ['compte' => Lien::compte()],
        ], 201)->withHeader('Location', Lien::compte()['href']);
    }
}
