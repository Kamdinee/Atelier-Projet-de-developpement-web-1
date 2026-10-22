<?php

declare(strict_types=1);

namespace Nrv\Tests\Application\UseCase;

use DateTimeImmutable;
use Nrv\Application\UseCase\ConsulterJauges;
use Nrv\Domain\ValueObject\JaugeSoiree;
use Nrv\Tests\Application\Mock\InMemoryJaugeRepository;
use PHPUnit\Framework\TestCase;

final class ConsulterJaugesTest extends TestCase
{
    public function testCalculeLaSyntheseDuFestival(): void
    {
        $jauge = static fn (int $id, int $jauge, int $vendues, int $recette): JaugeSoiree => new JaugeSoiree(
            $id, "Soirée {$id}", new DateTimeImmutable('2027-06-1' . $id . ' 20:00'), 'Lieu', $jauge, $vendues, $vendues, 0, $recette,
        );
        $useCase = new ConsulterJauges(new InMemoryJaugeRepository([
            $jauge(1, 750, 750, 1875000),
            $jauge(2, 750, 748, 1870000),
            $jauge(3, 5000, 2500, 7500000),
        ]));

        $jauges = $useCase->executer();

        $this->assertSame(6500, $jauges->jaugeTotale);
        $this->assertSame(3998, $jauges->placesVendues);
        $this->assertSame(61.5, $jauges->tauxRemplissage);
        $this->assertSame(11245000, $jauges->recette);
        $this->assertSame(1, $jauges->soireesCompletes);
        $this->assertSame(1, $jauges->soireesPresqueCompletes);
        $this->assertCount(3, $jauges->soirees);
        $this->assertTrue($jauges->soirees[0]->complete);
        $this->assertSame(2, $jauges->soirees[1]->placesRestantes);
    }

    public function testSansSoireeLeTauxVautZero(): void
    {
        $this->assertSame(0.0, (new ConsulterJauges(new InMemoryJaugeRepository([])))->executer()->tauxRemplissage);
    }
}
