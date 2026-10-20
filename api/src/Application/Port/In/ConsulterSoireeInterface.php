<?php

declare(strict_types=1);

namespace Nrv\Application\Port\In;

use Nrv\Application\Dto\SoireeDto;

// cas d'usage : afficher le detail d'une soiree
interface ConsulterSoireeInterface
{
    public function executer(int $id): SoireeDto;
}
