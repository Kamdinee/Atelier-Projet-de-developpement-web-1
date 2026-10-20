<?php

declare(strict_types=1);

namespace Nrv\Application\UseCase;

use Nrv\Application\Dto\SoireeDto;
use Nrv\Application\Port\In\ConsulterSoireeInterface;
use Nrv\Domain\Exception\SoireeIntrouvableException;
use Nrv\Domain\Repository\SoireeRepositoryInterface;

final class ConsulterSoiree implements ConsulterSoireeInterface
{
    public function __construct(private readonly SoireeRepositoryInterface $soirees)
    {
    }

    public function executer(int $id): SoireeDto
    {
        $soiree = $this->soirees->trouverParId($id);
        if ($soiree === null) {
            throw SoireeIntrouvableException::avecId($id);
        }

        return SoireeDto::depuis($soiree);
    }
}
