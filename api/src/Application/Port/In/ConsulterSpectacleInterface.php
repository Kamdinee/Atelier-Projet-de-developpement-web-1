<?php

declare(strict_types=1);

namespace Nrv\Application\Port\In;

use Nrv\Application\Dto\SpectacleDto;

// cas d'usage : afficher un spectacle
interface ConsulterSpectacleInterface
{
    public function executer(int $id): SpectacleDto;
}
