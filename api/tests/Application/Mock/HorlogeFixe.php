<?php

declare(strict_types=1);

namespace Nrv\Tests\Application\Mock;

use DateTimeImmutable;
use Nrv\Application\Port\Out\HorlogeInterface;

// heure fixe pour les tests
final class HorlogeFixe implements HorlogeInterface
{
    public function maintenant(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-10-22 20:00:00');
    }
}
