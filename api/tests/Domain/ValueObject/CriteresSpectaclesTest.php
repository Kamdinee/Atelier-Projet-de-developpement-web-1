<?php

declare(strict_types=1);

namespace Nrv\Tests\Domain\ValueObject;

use Nrv\Domain\Exception\DonneeInvalideException;
use Nrv\Domain\ValueObject\CriteresSpectacles;
use Nrv\Domain\ValueObject\ResultatPagine;
use PHPUnit\Framework\TestCase;

final class CriteresSpectaclesTest extends TestCase
{
    public function testParDefautPremierePageDeDouzeSansFiltre(): void
    {
        $criteres = new CriteresSpectacles();

        $this->assertNull($criteres->date());
        $this->assertNull($criteres->style());
        $this->assertSame(1, $criteres->page());
        $this->assertSame(12, $criteres->taille());
        $this->assertSame(0, $criteres->decalage());
    }

    public function testLeDecalageDependDeLaPage(): void
    {
        $criteres = new CriteresSpectacles(null, null, 3, 12);

        $this->assertSame(24, $criteres->decalage());
    }

    public function testUnePageZeroEstRefusee(): void
    {
        $this->expectException(DonneeInvalideException::class);

        new CriteresSpectacles(null, null, 0);
    }

    public function testUneTailleTropGrandeEstRefusee(): void
    {
        $this->expectException(DonneeInvalideException::class);

        new CriteresSpectacles(null, null, 1, CriteresSpectacles::TAILLE_MAX + 1);
    }

    public function testLeNombreDePagesEstArrondiAuDessus(): void
    {
        $this->assertSame(4, (new ResultatPagine([], 37, 1, 12))->nbPages());
        $this->assertSame(3, (new ResultatPagine([], 36, 1, 12))->nbPages());
    }

    public function testSansResultatIlYAQuandMemeUnePage(): void
    {
        $this->assertSame(1, (new ResultatPagine([], 0, 1, 12))->nbPages());
    }
}
