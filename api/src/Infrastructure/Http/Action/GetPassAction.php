<?php

declare(strict_types=1);

namespace Nrv\Infrastructure\Http\Action;

use Nrv\Application\UseCase\ListerPass;
use Nrv\Infrastructure\Http\Json;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// GET /pass : liste des formules de pass disponibles
final class GetPassAction
{
    public function __construct(
        private readonly ListerPass $listerPass,
    ) {
    }

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
    ): ResponseInterface {
        return Json::reponse($response, [
            'type' => 'collection',
            'pass' => $this->listerPass->executer(),
        ]);
    }
}
