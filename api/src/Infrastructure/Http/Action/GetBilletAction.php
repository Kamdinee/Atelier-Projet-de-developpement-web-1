<?php

declare(strict_types=1);

namespace Nrv\Infrastructure\Http\Action;

use Nrv\Application\Port\In\ConsulterBilletInterface;
use Nrv\Infrastructure\Http\Json;
use Nrv\Infrastructure\Http\Presenter\BilletJson;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// GET /billets/{token} : public, le jeton aleatoire de l'url sert de cle d'acces
final class GetBilletAction
{
    public function __construct(private readonly ConsulterBilletInterface $consulterBillet)
    {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $billet = $this->consulterBillet->executer((string) ($args['token'] ?? ''));

        // le billet donne acces a la soiree : il ne doit pas rester dans un cache partage
        return Json::reponse($response, ['type' => 'resource'] + BilletJson::complet($billet))
            ->withHeader('Cache-Control', 'private, no-store')
            ->withHeader('Referrer-Policy', 'no-referrer');
    }
}
