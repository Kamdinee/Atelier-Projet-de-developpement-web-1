<?php

declare(strict_types=1);

namespace Nrv\Infrastructure\Http\Action;

use Nrv\Application\Dto\PageSpectaclesDto;
use Nrv\Application\Dto\SpectacleDto;
use Nrv\Application\Port\In\ListerSpectaclesInterface;
use Nrv\Infrastructure\Http\Json;
use Nrv\Infrastructure\Http\Lien;
use Nrv\Infrastructure\Http\Validation\FiltreSpectaclesValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// GET /spectacles?date=AAAA-MM-JJ&style=slug&page=1&size=12
final class GetSpectaclesAction
{
    public function __construct(
        private readonly ListerSpectaclesInterface $listerSpectacles,
        private readonly FiltreSpectaclesValidator $validator,
    ) {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $filtre = $this->validator->valider($request->getQueryParams());
        $page = $this->listerSpectacles->executer($filtre);

        return Json::reponse($response, [
            'type' => 'collection',
            'count' => count($page->spectacles),
            'total' => $page->total,
            'page' => $page->page,
            'size' => $page->taille,
            'filtres' => [
                'date' => $filtre->date,
                'style' => $filtre->style,
            ],
            'spectacles' => array_map(
                static fn (SpectacleDto $s): array => [
                    'spectacle' => [
                        'id' => $s->id,
                        'titre' => $s->titre,
                        'date' => $s->date,
                        'horaire' => $s->horaire,
                        'image' => $s->imagePrincipale(),
                        'style' => ['slug' => $s->style->slug, 'libelle' => $s->style->libelle],
                    ],
                    'links' => [
                        'self' => Lien::spectacle($s->id),
                        'soiree' => Lien::soiree($s->soireeId),
                    ],
                ],
                $page->spectacles,
            ),
            'links' => $this->liensPagination($page),
        ]);
    }

    private function liensPagination(PageSpectaclesDto $page): array
    {
        $lien = static fn (int $numero): array => Lien::spectacles([
            'date' => $page->filtre->date,
            'style' => $page->filtre->style,
            'page' => $numero,
            'size' => $page->taille,
        ]);

        $liens = [
            'self' => $lien($page->page),
            'first' => $lien(1),
        ];
        if ($page->page > 1) {
            $liens['prev'] = $lien(min($page->page - 1, $page->nbPages));
        }
        if ($page->page < $page->nbPages) {
            $liens['next'] = $lien($page->page + 1);
        }
        $liens['last'] = $lien($page->nbPages);

        return $liens;
    }
}
