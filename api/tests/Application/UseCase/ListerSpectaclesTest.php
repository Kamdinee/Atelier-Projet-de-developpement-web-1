<?php

declare(strict_types=1);

namespace Nrv\Tests\Application\UseCase;

use Nrv\Application\Dto\FiltreSpectaclesDto;
use Nrv\Application\Dto\SpectacleDto;
use Nrv\Application\UseCase\ListerSpectacles;
use Nrv\Domain\Exception\DonneeInvalideException;
use Nrv\Tests\Application\Mock\InMemorySpectacleRepository;
use Nrv\Tests\Fixtures\Fabrique;
use PHPUnit\Framework\TestCase;

final class ListerSpectaclesTest extends TestCase
{
    private ListerSpectacles $useCase;

    protected function setUp(): void
    {
        $metal = Fabrique::style('metal', 1);
        $blues = Fabrique::style('blues-rock', 2);

        $this->useCase = new ListerSpectacles(new InMemorySpectacleRepository([
            Fabrique::spectacle(1, 1, '2027-06-11 20:00', $metal),
            Fabrique::spectacle(2, 1, '2027-06-11 21:30', $blues),
            Fabrique::spectacle(3, 2, '2027-06-12 20:00', $metal),
            Fabrique::spectacle(4, 2, '2027-06-12 21:30', $metal),
        ]));
    }

    public function testSansFiltreTousLesSpectaclesSontRenvoyes(): void
    {
        $page = $this->useCase->executer(new FiltreSpectaclesDto());

        $this->assertSame(4, $page->total);
        $this->assertCount(4, $page->spectacles);
        $this->assertContainsOnlyInstancesOf(SpectacleDto::class, $page->spectacles);
    }

    public function testFiltreParStyle(): void
    {
        $page = $this->useCase->executer(new FiltreSpectaclesDto(style: 'metal'));

        $this->assertSame(3, $page->total);
        $this->assertSame([1, 3, 4], $this->ids($page->spectacles));
    }

    public function testFiltreParDate(): void
    {
        $page = $this->useCase->executer(new FiltreSpectaclesDto(date: '2027-06-12'));

        $this->assertSame([3, 4], $this->ids($page->spectacles));
    }

    public function testFiltresDateEtStyleCombines(): void
    {
        $page = $this->useCase->executer(new FiltreSpectaclesDto(date: '2027-06-11', style: 'metal'));

        $this->assertSame([1], $this->ids($page->spectacles));
    }

    public function testPagination(): void
    {
        $page = $this->useCase->executer(new FiltreSpectaclesDto(page: 2, taille: 3));

        $this->assertSame(4, $page->total);
        $this->assertSame(2, $page->nbPages);
        $this->assertSame([4], $this->ids($page->spectacles));
    }

    public function testLeDtoContientLaDateEtLHoraire(): void
    {
        $spectacle = $this->useCase->executer(new FiltreSpectaclesDto(date: '2027-06-11'))->spectacles[0];

        $this->assertSame('2027-06-11', $spectacle->date);
        $this->assertSame('20:00', $spectacle->horaire);
        $this->assertSame('metal', $spectacle->style->slug);
        $this->assertSame('https://exemple.fr/1.jpg', $spectacle->imagePrincipale());
    }

    public function testUneDateMalFormeeEstRefusee(): void
    {
        $this->expectException(DonneeInvalideException::class);

        $this->useCase->executer(new FiltreSpectaclesDto(date: '11/06/2027'));
    }

    /**
     * @param list<SpectacleDto> $spectacles
     * @return list<int>
     */
    private function ids(array $spectacles): array
    {
        return array_map(static fn (SpectacleDto $s): int => $s->id, $spectacles);
    }
}
