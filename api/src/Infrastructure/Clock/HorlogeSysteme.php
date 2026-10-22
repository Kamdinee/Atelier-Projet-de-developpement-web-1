<?php

declare(strict_types=1);

namespace Nrv\Infrastructure\Clock;

use DateTimeImmutable;
use Nrv\Application\Port\Out\HorlogeInterface;

// adaptateur du port HorlogeInterface : l'heure du serveur
final class HorlogeSysteme implements HorlogeInterface
{
    public function maintenant(): DateTimeImmutable
    {
        return new DateTimeImmutable();
    }
}
