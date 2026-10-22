<?php

declare(strict_types=1);

namespace Nrv\Application\Port\In;

use Nrv\Application\Dto\SoireeResumeDto;

// cas d'usage : toutes les soirees du festival (choix des soirees d'un pass)
interface ListerSoireesInterface
{
    /** @return list<SoireeResumeDto> */
    public function executer(): array;
}
