<?php

declare(strict_types=1);

namespace Nrv\Infrastructure\Http\Action;

use Nrv\Application\Port\In\ConsulterSpectacleInterface;
use Nrv\Infrastructure\Http\Json;
use Nrv\Infrastructure\Http\Lien;
use Nrv\Infrastructure\Http\Presenter\SpectacleJson;
use Nrv\Infrastructure\Http\Validation\IdentifiantValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// GET /spectacles/{id}
final class GetSpectacleAction
{
    public function __construct(
        private readonly ConsulterSpectacleInterface $consulterSpectacle,
        private readonly IdentifiantValidator $validator,
    ) {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = $this->validator->entierPositif($args['id'] ?? null);
        $spectacle = $this->consulterSpectacle->executer($id);

        return Json::reponse($response, [
            'type' => 'resource',
            'spectacle' => SpectacleJson::complet($spectacle),
            'links' => [
                'self' => Lien::spectacle($spectacle->id),
                'soiree' => Lien::soiree($spectacle->soireeId),
            ],
        ]);
    }
}
