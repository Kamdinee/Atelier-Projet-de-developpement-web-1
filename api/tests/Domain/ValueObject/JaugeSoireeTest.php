<?php

declare(strict_types=1);

namespace Nrv\Tests\Domain\ValueObject;

use DateTimeImmutable;
use Nrv\Domain\Exception\DonneeInvalideException;
use Nrv\Domain\ValueObject\JaugeSoiree;
use PHPUnit\Framework\TestCase;

final class JaugeSoireeTest extends TestCase
{
    private function jauge(int $jauge, int $vendues): JaugeSoiree
    {
        return new JaugeSoiree(1, 'Nuit métal', new DateTimeImmutable('2027-06-11 19:30'), 'Zénith', $jauge, $vendues, $vendues, 0, $vendues * 3000);
    }

    public function testCalculeLesPlacesRestantesEtLeTaux(): void
    {
        $j = $this->jauge(5000, 3650);

        $this->assertSame(1350, $j->placesRestantes());
        $this->assertSame(73.0, $j->tauxRemplissage());
        $this->assertFalse($j->estComplete());
        $this->assertFalse($j->estPresqueComplete());
    }

    public function testUneSoireeAPlusDe90PourCentEstPresqueComplete(): void
    {
        $j = $this->jauge(750, 748);

        $this->assertSame(99.7, $j->tauxRemplissage());
        $this->assertTrue($j->estPresqueComplete());
        $this->assertFalse($j->estComplete());
    }

    public function testUneSoireeCompleteNestPasPresqueComplete(): void
    {
        $j = $this->jauge(750, 750);

        $this->assertTrue($j->estComplete());
        $this->assertFalse($j->estPresqueComplete());
    }

    public function testPlusDePlacesVenduesQueLaJaugeEstRefuse(): void
    {
        $this->expectException(DonneeInvalideException::class);

        $this->jauge(750, 751);
    }
}
