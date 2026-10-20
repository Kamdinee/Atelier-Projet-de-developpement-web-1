<?php

declare(strict_types=1);

namespace Nrv\Tests\Application\UseCase;

use Nrv\Application\UseCase\ConsulterSoiree;
use Nrv\Domain\Exception\SoireeIntrouvableException;
use Nrv\Tests\Application\Mock\InMemorySoireeRepository;
use Nrv\Tests\Fixtures\Fabrique;
use PHPUnit\Framework\TestCase;

final class ConsulterSoireeTest extends TestCase
{
    public function testRenvoieLeDetailDeLaSoiree(): void
    {
        $useCase = new ConsulterSoiree(new InMemorySoireeRepository([
            Fabrique::soiree(id: 1, jauge: 100, placesVendues: 98),
        ]));

        $soiree = $useCase->executer(1);

        $this->assertSame('Nuit métal', $soiree->nom);
        $this->assertSame('2027-06-11', $soiree->date);
        $this->assertSame('19:30', $soiree->heureDebut);
        $this->assertSame(3000, $soiree->tarifNormal);
        $this->assertSame(2000, $soiree->tarifReduit);
        $this->assertSame(2, $soiree->placesRestantes);
        $this->assertFalse($soiree->complete);
        $this->assertCount(2, $soiree->spectacles);
        $this->assertSame('Zénith de Nancy', $soiree->lieu['nom']);
    }

    public function testUneSoireeCompleteEstSignalee(): void
    {
        $useCase = new ConsulterSoiree(new InMemorySoireeRepository([
            Fabrique::soiree(id: 1, jauge: 100, placesVendues: 100),
        ]));

        $this->assertTrue($useCase->executer(1)->complete);
    }

    public function testUneSoireeInconnueLeveUneException(): void
    {
        $useCase = new ConsulterSoiree(new InMemorySoireeRepository([]));

        $this->expectException(SoireeIntrouvableException::class);

        $useCase->executer(42);
    }
}
