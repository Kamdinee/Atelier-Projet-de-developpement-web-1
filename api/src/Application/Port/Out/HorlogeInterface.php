<?php

declare(strict_types=1);

namespace Nrv\Application\Port\Out;

use DateTimeImmutable;

// port de sortie : l'heure courante (fixe dans les tests)
interface HorlogeInterface
{
    public function maintenant(): DateTimeImmutable;
}
