<?php

declare(strict_types=1);

namespace Nrv\Infrastructure\Http\Action;

use Nrv\Application\Dto\StyleDto;
use Nrv\Application\Port\In\GererProgrammeInterface;
use Nrv\Infrastructure\Http\Json;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// GET /admin/referentiels : lieux et styles proposes dans les formulaires d'ajout
final class GetReferentielsAdminAction
{
    public function __construct(private readonly GererProgrammeInterface $programme)
    {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $referentiels = $this->programme->referentiels();

        return Json::reponse($response, [
            'type' => 'resource',
            'lieux' => $referentiels->lieux,
            'styles' => array_map(
                static fn (StyleDto $s): array => ['slug' => $s->slug, 'libelle' => $s->libelle],
                $referentiels->styles,
            ),
        ]);
    }
}
