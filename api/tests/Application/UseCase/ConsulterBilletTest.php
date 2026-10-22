<?php

declare(strict_types=1);

namespace Nrv\Tests\Application\UseCase;

use Nrv\Application\UseCase\ConsulterBillet;
use Nrv\Domain\Exception\BilletIntrouvableException;
use Nrv\Domain\ValueObject\TypeBillet;
use Nrv\Tests\Application\Mock\InMemoryBilletRepository;
use Nrv\Tests\Fixtures\Fabrique;
use PHPUnit\Framework\TestCase;

final class ConsulterBilletTest extends TestCase
{
    public function testRenvoieLeBilletDuJetonAvecSesSoirees(): void
    {
        $useCase = new ConsulterBillet(new InMemoryBilletRepository([Fabrique::billet(TypeBillet::Pass)]));

        $billet = $useCase->executer(strtoupper(Fabrique::JETON));

        $this->assertSame('PASS', $billet->type);
        $this->assertSame('Marc Durand', $billet->nomAcheteur);
        $this->assertSame(8730, $billet->prix);
        $this->assertCount(3, $billet->soirees);
        $this->assertSame('2027-06-11', $billet->soirees[0]['date']);
        $this->assertSame('19:30', $billet->soirees[0]['heure_debut']);
        $this->assertSame('Zénith de Nancy', $billet->soirees[0]['lieu']['nom']);
    }

    public function testUnJetonInconnuDonneIntrouvable(): void
    {
        $useCase = new ConsulterBillet(new InMemoryBilletRepository([Fabrique::billet()]));

        $this->expectException(BilletIntrouvableException::class);

        $useCase->executer('1c7e4b20-9d3a-4f8e-b6a1-0e5f2c8d7a44');
    }

    public function testUnJetonMalFormeDonneIntrouvableSansInterrogerLeDepot(): void
    {
        $useCase = new ConsulterBillet(new InMemoryBilletRepository([]));

        $this->expectException(BilletIntrouvableException::class);

        $useCase->executer("x' OR 1=1 --");
    }
}
