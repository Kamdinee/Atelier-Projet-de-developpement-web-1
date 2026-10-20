<?php

declare(strict_types=1);

namespace Nrv\Infrastructure\Http\Action;

use Nrv\Application\Dto\SpectacleDto;
use Nrv\Application\Port\In\ConsulterSoireeInterface;
use Nrv\Infrastructure\Http\Json;
use Nrv\Infrastructure\Http\Lien;
use Nrv\Infrastructure\Http\Presenter\SpectacleJson;
use Nrv\Infrastructure\Http\Validation\IdentifiantValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// GET /soirees/{id} : la soiree et ses 2 a 3 spectacles
final class GetSoireeDetailAction
{
    public function __construct(
        private readonly ConsulterSoireeInterface $consulterSoiree,
        private readonly IdentifiantValidator $validator,
    ) {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = $this->validator->entierPositif($args['id'] ?? null);
        $soiree = $this->consulterSoiree->executer($id);

        return Json::reponse($response, [
            'type' => 'resource',
            'soiree' => [
                'id' => $soiree->id,
                'nom' => $soiree->nom,
                'thematique' => $soiree->thematique,
                'date' => $soiree->date,
                'heure_debut' => $soiree->heureDebut,
                'lieu' => $soiree->lieu,
                'tarifs' => [
                    'normal' => $soiree->tarifNormal,
                    'reduit' => $soiree->tarifReduit,
                ],
                'jauge' => $soiree->jauge,
                'places_restantes' => $soiree->placesRestantes,
                'complete' => $soiree->complete,
                'spectacles' => array_map(
                    static fn (SpectacleDto $s): array => SpectacleJson::complet($s)
                        + ['links' => ['self' => Lien::spectacle($s->id)]],
                    $soiree->spectacles,
                ),
            ],
            'links' => [
                'self' => Lien::soiree($soiree->id),
                'spectacles' => Lien::spectacles(['date' => $soiree->date]),
            ],
        ]);
    }
}
