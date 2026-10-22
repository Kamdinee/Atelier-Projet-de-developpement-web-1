<?php

declare(strict_types=1);

namespace Nrv\Application\Port\In;

use Nrv\Application\Dto\BilletDto;

// cas d'usage : afficher un billet a partir du jeton de son url
interface ConsulterBilletInterface
{
    public function executer(string $jeton): BilletDto;
}
