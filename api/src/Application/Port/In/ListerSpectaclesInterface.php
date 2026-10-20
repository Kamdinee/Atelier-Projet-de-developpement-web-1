<?php

declare(strict_types=1);

namespace Nrv\Application\Port\In;

use Nrv\Application\Dto\FiltreSpectaclesDto;
use Nrv\Application\Dto\PageSpectaclesDto;

// cas d'usage : afficher la liste filtree des spectacles
interface ListerSpectaclesInterface
{
    public function executer(FiltreSpectaclesDto $filtre): PageSpectaclesDto;
}
