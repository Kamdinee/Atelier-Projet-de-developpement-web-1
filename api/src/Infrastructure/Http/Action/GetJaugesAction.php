<?php

declare(strict_types=1);

namespace Nrv\Infrastructure\Http\Action;

use Nrv\Application\Dto\JaugeSoireeDto;
use Nrv\Application\Port\In\ConsulterJaugesInterface;
use Nrv\Infrastructure\Http\Json;
use Nrv\Infrastructure\Http\Lien;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// GET /admin/jauges : places vendues et recette par soiree (F11), reserve aux admins
final class GetJaugesAction
{
    public function __construct(private readonly ConsulterJaugesInterface $consulterJauges)
    {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $jauges = $this->consulterJauges->executer();

        return Json::reponse($response, [
            'type' => 'collection',
            'count' => count($jauges->soirees),
            'synthese' => [
                'jauge' => $jauges->jaugeTotale,
                'places_vendues' => $jauges->placesVendues,
                'taux_remplissage' => $jauges->tauxRemplissage,
                'recette' => $jauges->recette,
                'soirees_completes' => $jauges->soireesCompletes,
                'soirees_presque_completes' => $jauges->soireesPresqueCompletes,
            ],
            'soirees' => array_map(static fn (JaugeSoireeDto $s): array => [
                'soiree' => [
                    'id' => $s->soireeId,
                    'nom' => $s->nom,
                    'date' => $s->date,
                    'heure_debut' => $s->heureDebut,
                    'lieu' => $s->lieu,
                    'jauge' => $s->jauge,
                    'places_vendues' => $s->placesVendues,
                    'places_restantes' => $s->placesRestantes,
                    'taux_remplissage' => $s->tauxRemplissage,
                    'billets_soiree' => $s->billetsSoiree,
                    'billets_pass' => $s->billetsPass,
                    'recette' => $s->recette,
                    'complete' => $s->complete,
                    'presque_complete' => $s->presqueComplete,
                ],
                'links' => ['soiree' => Lien::soiree($s->soireeId)],
            ], $jauges->soirees),
        ])->withHeader('Cache-Control', 'private, no-store');
    }
}
