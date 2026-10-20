<?php

declare(strict_types=1);

namespace Nrv\Tests\Domain\Entity;

use Nrv\Domain\Entity\Soiree;
use Nrv\Domain\Exception\DonneeInvalideException;
use Nrv\Domain\Exception\NombreSpectaclesInvalideException;
use Nrv\Domain\Exception\PlacesInsuffisantesException;
use Nrv\Domain\Exception\QuantiteInvalideException;
use Nrv\Domain\Exception\SoireeCompleteException;
use Nrv\Domain\ValueObject\CategorieTarif;
use Nrv\Tests\Fixtures\Fabrique;
use PHPUnit\Framework\TestCase;

final class SoireeTest extends TestCase
{
    // ---------- 2 a 3 spectacles ----------

    public function testUneSoireeAvecDeuxSpectaclesEstValide(): void
    {
        $soiree = Fabrique::soiree();

        $this->assertCount(2, $soiree->spectacles());
    }

    public function testUneSoireeAvecTroisSpectaclesEstValide(): void
    {
        $soiree = Fabrique::soiree(spectacles: [
            Fabrique::spectacle(1),
            Fabrique::spectacle(2),
            Fabrique::spectacle(3),
        ]);

        $this->assertCount(3, $soiree->spectacles());
    }

    public function testUneSoireeAvecUnSeulSpectacleEstRefusee(): void
    {
        $this->expectException(NombreSpectaclesInvalideException::class);

        Fabrique::soiree(spectacles: [Fabrique::spectacle(1)]);
    }

    public function testUneSoireeAvecQuatreSpectaclesEstRefusee(): void
    {
        $this->expectException(NombreSpectaclesInvalideException::class);

        Fabrique::soiree(spectacles: [
            Fabrique::spectacle(1),
            Fabrique::spectacle(2),
            Fabrique::spectacle(3),
            Fabrique::spectacle(4),
        ]);
    }

    public function testUnSpectacleDUneAutreSoireeEstRefuse(): void
    {
        $this->expectException(DonneeInvalideException::class);

        Fabrique::soiree(id: 1, spectacles: [Fabrique::spectacle(1, 1), Fabrique::spectacle(2, 99)]);
    }

    public function testLesSpectaclesSontTriesParHoraire(): void
    {
        $soiree = Fabrique::soiree(spectacles: [
            Fabrique::spectacle(1, 1, '2027-06-11 22:40'),
            Fabrique::spectacle(2, 1, '2027-06-11 20:00'),
            Fabrique::spectacle(3, 1, '2027-06-11 21:20'),
        ]);

        $ids = array_map(static fn ($s) => $s->id(), $soiree->spectacles());

        $this->assertSame([2, 3, 1], $ids);
    }

    // ---------- tarifs ----------

    public function testLePrixDependDeLaCategorie(): void
    {
        $soiree = Fabrique::soiree();

        $this->assertSame(3000, $soiree->prix(CategorieTarif::Normal));
        $this->assertSame(2000, $soiree->prix(CategorieTarif::Reduit));
    }

    // ---------- jauge ----------

    public function testLesPlacesRestantesSontLaJaugeMoinsLesVentes(): void
    {
        $soiree = Fabrique::soiree(jauge: 100, placesVendues: 40);

        $this->assertSame(60, $soiree->placesRestantes());
        $this->assertFalse($soiree->estComplete());
    }

    public function testReserverDecrementeLaJauge(): void
    {
        $soiree = Fabrique::soiree(jauge: 100, placesVendues: 40);

        $soiree->reserver(5);

        $this->assertSame(45, $soiree->placesVendues());
        $this->assertSame(55, $soiree->placesRestantes());
    }

    public function testReserverLesDernieresPlacesRendLaSoireeComplete(): void
    {
        $soiree = Fabrique::soiree(jauge: 100, placesVendues: 98);

        $soiree->reserver(2);

        $this->assertTrue($soiree->estComplete());
        $this->assertSame(0, $soiree->placesRestantes());
    }

    public function testReserverPlusQueLesPlacesRestantesEstRefuse(): void
    {
        $soiree = Fabrique::soiree(id: 7, jauge: 100, placesVendues: 98, spectacles: [
            Fabrique::spectacle(1, 7),
            Fabrique::spectacle(2, 7),
        ]);

        try {
            $soiree->reserver(3);
            $this->fail('une exception etait attendue');
        } catch (PlacesInsuffisantesException $e) {
            $this->assertNotInstanceOf(SoireeCompleteException::class, $e);
            $this->assertSame(7, $e->soireeId);
            $this->assertSame(3, $e->demandees);
            $this->assertSame(2, $e->restantes);
        }

        // la jauge n'a pas bouge
        $this->assertSame(98, $soiree->placesVendues());
    }

    public function testReserverSurUneSoireeCompleteEstRefuse(): void
    {
        $soiree = Fabrique::soiree(jauge: 100, placesVendues: 100);

        $this->expectException(SoireeCompleteException::class);

        $soiree->reserver(1);
    }

    public function testReserverZeroPlaceEstRefuse(): void
    {
        $soiree = Fabrique::soiree();

        $this->expectException(QuantiteInvalideException::class);

        $soiree->reserver(0);
    }

    public function testPeutAccueillir(): void
    {
        $soiree = Fabrique::soiree(jauge: 100, placesVendues: 97);

        $this->assertTrue($soiree->peutAccueillir(3));
        $this->assertFalse($soiree->peutAccueillir(4));
        $this->assertFalse($soiree->peutAccueillir(0));
    }

    public function testLaJaugeNePeutPasDepasserLaCapaciteDuLieu(): void
    {
        $this->expectException(DonneeInvalideException::class);

        Fabrique::soiree(jauge: 150, capacite: 100);
    }

    public function testLesPlacesVenduesNePeuventPasDepasserLaJauge(): void
    {
        $this->expectException(DonneeInvalideException::class);

        Fabrique::soiree(jauge: 100, placesVendues: 101);
    }

    public function testUneJaugeNulleEstRefusee(): void
    {
        $this->expectException(DonneeInvalideException::class);

        Fabrique::soiree(jauge: 0);
    }

    public function testLesBornesDuNombreDeSpectacles(): void
    {
        $this->assertSame(2, Soiree::MIN_SPECTACLES);
        $this->assertSame(3, Soiree::MAX_SPECTACLES);
    }
}
