<?php

declare(strict_types=1);

namespace Nrv\Application\Port\Out;

use Nrv\Domain\Entity\Soiree;

// port de sortie (output port) : acces aux soirees, implemente par un adaptateur de persistance
interface SoireeRepositoryInterface
{
    // soiree complete avec son lieu et ses 2 a 3 spectacles, null si inconnue
    public function trouverParId(int $id): ?Soiree;
}
