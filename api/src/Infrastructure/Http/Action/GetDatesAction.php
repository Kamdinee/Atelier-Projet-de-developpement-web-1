<?php

declare(strict_types=1);

namespace Nrv\Infrastructure\Http\Action;

use Nrv\Application\Port\In\ListerFiltresProgrammeInterface;
use Nrv\Infrastructure\Http\Json;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// GET /dates : liste du filtre par date
final class GetDatesAction
{
    public function __construct(private readonly ListerFiltresProgrammeInterface $filtres)
    {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return Json::reponse($response, [
            'type' => 'collection',
            'dates' => $this->filtres->dates(),
        ]);
    }
}
