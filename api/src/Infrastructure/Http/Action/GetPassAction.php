<?php

declare(strict_types=1);

namespace Nrv\Infrastructure\Http\Action;

use Nrv\Application\Dto\PassDto;
use Nrv\Application\Port\In\ConsulterPassInterface;
use Nrv\Infrastructure\Http\Json;
use Nrv\Infrastructure\Http\Lien;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// GET /pass : formules de pass (Pass 3 jours) et leurs tarifs en centimes
final class GetPassAction
{
    public function __construct(private readonly ConsulterPassInterface $consulterPass)
    {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $pass = $this->consulterPass->executer();

        return Json::reponse($response, [
            'type' => 'collection',
            'count' => count($pass),
            'pass' => array_map(static fn (PassDto $p): array => [
                'id' => $p->id,
                'libelle' => $p->libelle,
                'nb_soirees' => $p->nbSoirees,
                'tarifs' => ['normal' => $p->tarifNormal, 'reduit' => $p->tarifReduit],
            ], $pass),
            'links' => ['self' => Lien::pass(), 'soirees' => Lien::soirees()],
        ]);
    }
}
