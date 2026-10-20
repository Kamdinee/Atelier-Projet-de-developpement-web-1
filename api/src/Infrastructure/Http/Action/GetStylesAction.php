<?php

declare(strict_types=1);

namespace Nrv\Infrastructure\Http\Action;

use Nrv\Application\Dto\StyleDto;
use Nrv\Application\Port\In\ListerFiltresProgrammeInterface;
use Nrv\Infrastructure\Http\Json;
use Nrv\Infrastructure\Http\Lien;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// GET /styles : liste du filtre par style
final class GetStylesAction
{
    public function __construct(private readonly ListerFiltresProgrammeInterface $filtres)
    {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return Json::reponse($response, [
            'type' => 'collection',
            'styles' => array_map(
                static fn (StyleDto $s): array => [
                    'slug' => $s->slug,
                    'libelle' => $s->libelle,
                    'links' => ['spectacles' => Lien::spectacles(['style' => $s->slug])],
                ],
                $this->filtres->styles(),
            ),
        ]);
    }
}
