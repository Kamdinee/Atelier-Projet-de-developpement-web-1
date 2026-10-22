<?php

declare(strict_types=1);

namespace Nrv\Infrastructure\Http\Action;

use Nrv\Application\Port\In\GererProgrammeInterface;
use Nrv\Infrastructure\Http\Json;
use Nrv\Infrastructure\Http\Lien;
use Nrv\Infrastructure\Http\Validation\IdentifiantValidator;
use Nrv\Infrastructure\Http\Validation\ProgrammeAdminValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// POST /admin/soirees/{id}/spectacles : ajoute un spectacle a une soiree qui en a moins de 3 (E3)
final class PostSpectacleAction
{
    public function __construct(
        private readonly GererProgrammeInterface $programme,
        private readonly ProgrammeAdminValidator $validator,
        private readonly IdentifiantValidator $identifiant,
    ) {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $soireeId = $this->identifiant->entierPositif($args['id'] ?? null);
        $id = $this->programme->ajouterSpectacle($soireeId, $this->validator->spectacle($request->getParsedBody()));
        $lien = Lien::spectacle($id);

        return Json::reponse($response, [
            'type' => 'resource',
            'spectacle' => ['id' => $id],
            'links' => ['self' => $lien, 'soiree' => Lien::soiree($soireeId)],
        ], 201)->withHeader('Location', $lien['href']);
    }
}
