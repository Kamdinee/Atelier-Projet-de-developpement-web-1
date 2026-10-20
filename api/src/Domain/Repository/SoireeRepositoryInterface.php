<?php

declare(strict_types=1);

namespace Nrv\Domain\Repository;

use Nrv\Domain\Entity\Soiree;

// port de sortie : acces aux soirees
interface SoireeRepositoryInterface
{
    // soiree complete avec son lieu et ses 2 a 3 spectacles, null si inconnue
    public function trouverParId(int $id): ?Soiree;
}
