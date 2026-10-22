<?php

declare(strict_types=1);

namespace Nrv\Infrastructure\Http\Action;

use Nrv\Application\UseCase\InscrireUtilisateur;
use Nrv\Infrastructure\Http\Json;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpConflictException;
use Slim\Exception\HttpUnprocessableEntityException;

final class InscriptionAction
{
    public function __construct(
        private readonly InscrireUtilisateur $inscrireUtilisateur,
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
        $nom = $data['nom'] ?? null;
        $prenom = $data['prenom'] ?? null;
        $motDePasse = $data['mot_de_passe'] ?? null;

        if (
            !is_string($email)
            || !is_string($nom)
            || !is_string($prenom)
            || !is_string($motDePasse)
        ) {
            throw new HttpBadRequestException(
                $request,
                'Les champs email, nom, prenom et mot_de_passe sont obligatoires.',
            );
        }

        try {
            $utilisateur = $this->inscrireUtilisateur->executer(
                $email,
                $nom,
                $prenom,
                $motDePasse,
            );
        } catch (RuntimeException $e) {
            if (str_contains($e->getMessage(), 'déjà utilisée')) {
                throw new HttpConflictException($request, $e->getMessage());
            }

            throw new HttpUnprocessableEntityException($request, $e->getMessage());
        }

        return Json::reponse($response, [
            'message' => 'Compte créé avec succès.',
            'utilisateur' => $utilisateur,
        ], 201);
    }
}
