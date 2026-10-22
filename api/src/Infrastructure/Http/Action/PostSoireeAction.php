<?php

declare(strict_types=1);

namespace Nrv\Infrastructure\Http\Action;

use Nrv\Application\Port\In\GererProgrammeInterface;
use Nrv\Infrastructure\Http\Json;
use Nrv\Infrastructure\Http\Lien;
use Nrv\Infrastructure\Http\Validation\ProgrammeAdminValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// POST /admin/soirees : cree une soiree et ses 2 a 3 spectacles (E3)
final class PostSoireeAction
{
    public function __construct(
        private readonly GererProgrammeInterface $programme,
        private readonly ProgrammeAdminValidator $validator,
    ) {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $id = $this->programme->ajouterSoiree($this->validator->soiree($request->getParsedBody()));
        $lien = Lien::soiree($id);

        return Json::reponse($response, [
            'type' => 'resource',
            'soiree' => ['id' => $id],
            'links' => ['self' => $lien],
        ], 201)->withHeader('Location', $lien['href']);
    }
}
