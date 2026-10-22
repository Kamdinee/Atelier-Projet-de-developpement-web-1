<?php

declare(strict_types=1);

namespace Nrv\Application\Port\In;

use Nrv\Application\Dto\JaugesDto;

// cas d'usage : backoffice, jauge et recette de chaque soiree (F11)
interface ConsulterJaugesInterface
{
    public function executer(): JaugesDto;
}
