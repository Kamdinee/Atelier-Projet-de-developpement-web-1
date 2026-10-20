<?php

declare(strict_types=1);

namespace Nrv\Infrastructure\Http\Action;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// point d'entree de l'api : liste les ressources principales
final class AccueilAction
{
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = [
            'nom' => 'nrv.net api',
            'links' => [
                'spectacles' => ['href' => '/spectacles'],
                'styles' => ['href' => '/styles'],
                'dates' => ['href' => '/dates'],
            ],
        ];

        $response->getBody()->write((string) json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
}
