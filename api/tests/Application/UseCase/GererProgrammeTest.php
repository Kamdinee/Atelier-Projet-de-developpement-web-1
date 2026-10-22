<?php

declare(strict_types=1);

namespace Nrv\Tests\Application\UseCase;

use Nrv\Application\Dto\NouveauSpectacleDto;
use Nrv\Application\Dto\NouvelleSoireeDto;
use Nrv\Application\UseCase\GererProgramme;
use Nrv\Domain\Entity\Lieu;
use Nrv\Domain\Exception\DateDejaPriseException;
use Nrv\Domain\Exception\DonneeInvalideException;
use Nrv\Domain\Exception\NombreSpectaclesInvalideException;
use Nrv\Domain\Exception\SoireeIntrouvableException;
use Nrv\Domain\Exception\TarifInvalideException;
use Nrv\Tests\Application\Mock\InMemoryProgrammeAdminRepository;
use Nrv\Tests\Application\Mock\InMemorySoireeRepository;
use Nrv\Tests\Fixtures\Fabrique;
use PHPUnit\Framework\TestCase;

final class GererProgrammeTest extends TestCase
{
    private InMemoryProgrammeAdminRepository $depot;

    private function useCase(): GererProgramme
    {
        $this->depot = new InMemoryProgrammeAdminRepository(
            [new Lieu(3, 'Salle Poirel', '3 rue Victor Poirel', 750)],
            [Fabrique::style('metal', 1), Fabrique::style('folk', 2)],
            ['2027-06-11'],
        );
        $troisSpectacles = Fabrique::soiree(id: 8, spectacles: [
            Fabrique::spectacle(1, 8, '2027-06-11 20:00'),
            Fabrique::spectacle(2, 8, '2027-06-11 21:20'),
            Fabrique::spectacle(3, 8, '2027-06-11 22:40'),
        ]);

        return new GererProgramme($this->depot, new InMemorySoireeRepository([Fabrique::soiree(id: 4), $troisSpectacles]));
    }

    private function spectacle(string $titre = 'Iron Lorraine', string $style = 'metal', string $horaire = '20:30'): NouveauSpectacleDto
    {
        return new NouveauSpectacleDto($titre, 'Riffs massifs.', $style, [$titre, ' Invité '], $horaire, null, 'img/spectacles/metal-1.webp');
    }

    /**
     * @param list<NouveauSpectacleDto>|null $spectacles
     */
    private function soiree(?array $spectacles = null, string $date = '2027-06-18', ?int $jauge = null, int $reduit = 2000): NouvelleSoireeDto
    {
        return new NouvelleSoireeDto('Nuit bonus', 'Soirée métal', $date, '19:30', 3, $jauge, 3000, $reduit,
            $spectacles ?? [$this->spectacle(), $this->spectacle('Fer Rouge', 'folk', '22:00')]);
    }

    public function testAjouteUneSoireeAvecSesSpectacles(): void
    {
        $useCase = $this->useCase();

        $id = $useCase->ajouterSoiree($this->soiree());

        $this->assertSame(101, $id);
        $soiree = $this->depot->soireesAjoutees[0];
        $this->assertSame(750, $soiree->jauge(), 'jauge par defaut = capacite du lieu');
        $this->assertSame(0, $soiree->placesVendues());
        $this->assertSame('2027-06-18 19:30', $soiree->debut()->format('Y-m-d H:i'));
        $this->assertCount(2, $soiree->spectacles());
        $this->assertSame(['Iron Lorraine', 'Invité'], $soiree->spectacles()[0]->artistes());
        $this->assertSame('folk', $soiree->spectacles()[1]->style()->slug());
        $this->assertSame('2027-06-18 22:00', $soiree->spectacles()[1]->horaire()->format('Y-m-d H:i'));
    }

    public function testUneDateDejaPriseEstRefusee(): void
    {
        $this->expectException(DateDejaPriseException::class);

        $this->useCase()->ajouterSoiree($this->soiree(date: '2027-06-11'));
    }

    public function testUneSoireeDUnSeulSpectacleEstRefusee(): void
    {
        $this->expectException(NombreSpectaclesInvalideException::class);

        $this->useCase()->ajouterSoiree($this->soiree([$this->spectacle()]));
    }

    public function testUneJaugeSuperieureALaCapaciteEstRefusee(): void
    {
        $this->expectException(DonneeInvalideException::class);

        $this->useCase()->ajouterSoiree($this->soiree(jauge: 800));
    }

    public function testUnTarifReduitSuperieurAuNormalEstRefuse(): void
    {
        $this->expectException(TarifInvalideException::class);

        $this->useCase()->ajouterSoiree($this->soiree(reduit: 3500));
    }

    public function testUnStyleInconnuEstRefuse(): void
    {
        $this->expectException(DonneeInvalideException::class);

        $this->useCase()->ajouterSoiree($this->soiree([$this->spectacle(), $this->spectacle('X', 'jazz')]));
    }

    public function testAjouteUnTroisiemeSpectacleAUneSoiree(): void
    {
        $useCase = $this->useCase();

        $id = $useCase->ajouterSpectacle(4, $this->spectacle('Nordic Anvil', 'metal', '23:00'));

        $this->assertSame(501, $id);
        $this->assertSame(4, $this->depot->spectaclesAjoutes[0]->soireeId());
        $this->assertSame('2027-06-11 23:00', $this->depot->spectaclesAjoutes[0]->horaire()->format('Y-m-d H:i'));
    }

    public function testPasDeQuatriemeSpectacle(): void
    {
        $useCase = $this->useCase();

        try {
            $useCase->ajouterSpectacle(8, $this->spectacle());
            $this->fail('un 4e spectacle aurait du etre refuse');
        } catch (NombreSpectaclesInvalideException) {
            $this->assertSame([], $this->depot->spectaclesAjoutes);
        }
    }

    public function testSoireeInconnue(): void
    {
        $this->expectException(SoireeIntrouvableException::class);

        $this->useCase()->ajouterSpectacle(99, $this->spectacle());
    }

    public function testReferentiels(): void
    {
        $referentiels = $this->useCase()->referentiels();

        $this->assertSame('Salle Poirel', $referentiels->lieux[0]['nom']);
        $this->assertSame(750, $referentiels->lieux[0]['capacite']);
        $this->assertCount(2, $referentiels->styles);
    }
}
