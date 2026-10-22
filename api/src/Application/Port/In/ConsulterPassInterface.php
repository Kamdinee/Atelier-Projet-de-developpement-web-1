<?php

declare(strict_types=1);

namespace Nrv\Application\Port\In;

use Nrv\Application\Dto\PassDto;

// cas d'usage : formules de pass et leurs tarifs
interface ConsulterPassInterface
{
    /** @return list<PassDto> */
    public function executer(): array;
}
