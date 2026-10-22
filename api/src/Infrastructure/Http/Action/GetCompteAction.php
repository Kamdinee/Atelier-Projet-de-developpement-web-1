<?php

declare(strict_types=1);

namespace Nrv\Infrastructure\Http\Action;

use Nrv\Application\Port\In\ConsulterCompteInterface;
use Nrv\Infrastructure\Http\Json;
use Nrv\Infrastructure\Http\Lien;
use Nrv\Infrastructure\Http\Middleware\AuthMiddleware;
use Nrv\Infrastructure\Http\Presenter\UtilisateurJson;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// GET /compte : le compte de l'utilisateur connecte (route protegee par AuthMiddleware)
final class GetCompteAction
{
    public function __construct(private readonly ConsulterCompteInterface $consulterCompte)
    {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $porteur = $request->getAttribute(AuthMiddleware::ATTRIBUT);
        $compte = $this->consulterCompte->executer($porteur['id']);

        return Json::reponse($response, [
            'type' => 'resource',
            'utilisateur' => UtilisateurJson::compte($compte),
            'links' => ['self' => Lien::compte()],
        ]);
    }
}
