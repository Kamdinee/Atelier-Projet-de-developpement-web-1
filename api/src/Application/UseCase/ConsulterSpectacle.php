<?php

declare(strict_types=1);

namespace Nrv\Application\UseCase;

use Nrv\Application\Dto\SpectacleDto;
use Nrv\Application\Port\In\ConsulterSpectacleInterface;
use Nrv\Application\Port\Out\SpectacleRepositoryInterface;
use Nrv\Domain\Exception\SpectacleIntrouvableException;

final class ConsulterSpectacle implements ConsulterSpectacleInterface
{
    public function __construct(private readonly SpectacleRepositoryInterface $spectacles)
    {
    }

    public function executer(int $id): SpectacleDto
    {
        $spectacle = $this->spectacles->trouverParId($id);
        if ($spectacle === null) {
            throw SpectacleIntrouvableException::avecId($id);
        }

        return SpectacleDto::depuis($spectacle);
    }
}
