<?php

declare(strict_types=1);

namespace Nrv\Tests\Application\UseCase;

use Nrv\Application\UseCase\ConsulterSpectacle;
use Nrv\Application\UseCase\ListerFiltresProgramme;
use Nrv\Domain\Exception\SpectacleIntrouvableException;
use Nrv\Tests\Application\Mock\InMemorySpectacleRepository;
use Nrv\Tests\Fixtures\Fabrique;
use PHPUnit\Framework\TestCase;

final class ConsulterSpectacleTest extends TestCase
{
    private InMemorySpectacleRepository $depot;

    protected function setUp(): void
    {
        $this->depot = new InMemorySpectacleRepository([
            Fabrique::spectacle(1, 1, '2027-06-12 20:00', Fabrique::style('metal')),
            Fabrique::spectacle(2, 2, '2027-06-11 20:00', Fabrique::style('chanson', 2)),
        ]);
    }

    public function testRenvoieLeSpectacle(): void
    {
        $spectacle = (new ConsulterSpectacle($this->depot))->executer(2);

        $this->assertSame('Spectacle 2', $spectacle->titre);
        $this->assertSame(['Artiste 2'], $spectacle->artistes);
        $this->assertSame(2, $spectacle->soireeId);
    }

    public function testUnSpectacleInconnuLeveUneException(): void
    {
        $this->expectException(SpectacleIntrouvableException::class);

        (new ConsulterSpectacle($this->depot))->executer(99);
    }

    public function testLesFiltresListentStylesEtDatesTries(): void
    {
        $filtres = new ListerFiltresProgramme($this->depot);

        $this->assertSame(['chanson', 'metal'], array_map(static fn ($s) => $s->slug, $filtres->styles()));
        $this->assertSame(['2027-06-11', '2027-06-12'], $filtres->dates());
    }
}
