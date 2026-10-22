<?php

declare(strict_types=1);

namespace Nrv\Infrastructure\Http\Action;

use Nrv\Application\Dto\SoireeResumeDto;
use Nrv\Application\Port\In\ListerSoireesInterface;
use Nrv\Infrastructure\Http\Json;
use Nrv\Infrastructure\Http\Lien;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// GET /soirees : les 14 soirees avec leurs places restantes (choix des soirees d'un pass)
final class GetSoireesAction
{
    public function __construct(private readonly ListerSoireesInterface $listerSoirees)
    {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $soirees = $this->listerSoirees->executer();

        return Json::reponse($response, [
            'type' => 'collection',
            'count' => count($soirees),
            'soirees' => array_map(static fn (SoireeResumeDto $s): array => [
                'soiree' => [
                    'id' => $s->id,
                    'nom' => $s->nom,
                    'thematique' => $s->thematique,
                    'date' => $s->date,
                    'heure_debut' => $s->heureDebut,
                    'lieu' => $s->lieu,
                    'tarifs' => ['normal' => $s->tarifNormal, 'reduit' => $s->tarifReduit],
                    'places_restantes' => $s->placesRestantes,
                    'complete' => $s->complete,
                ],
                'links' => ['self' => Lien::soiree($s->id)],
            ], $soirees),
            'links' => ['self' => Lien::soirees(), 'pass' => Lien::pass()],
        ]);
    }
}
