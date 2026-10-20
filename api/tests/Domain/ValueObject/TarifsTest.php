<?php

declare(strict_types=1);

namespace Nrv\Tests\Domain\ValueObject;

use Nrv\Domain\Exception\TarifInvalideException;
use Nrv\Domain\ValueObject\CategorieTarif;
use Nrv\Domain\ValueObject\Tarifs;
use PHPUnit\Framework\TestCase;

final class TarifsTest extends TestCase
{
    public function testDonneLeBonTarifSelonLaCategorie(): void
    {
        $tarifs = new Tarifs(3000, 2000);

        $this->assertSame(3000, $tarifs->pour(CategorieTarif::Normal));
        $this->assertSame(2000, $tarifs->pour(CategorieTarif::Reduit));
    }

    public function testUnTarifReduitEgalAuNormalEstAccepte(): void
    {
        $tarifs = new Tarifs(2500, 2500);

        $this->assertSame(2500, $tarifs->reduit());
    }

    public function testLeTarifReduitNePeutPasDepasserLeNormal(): void
    {
        $this->expectException(TarifInvalideException::class);

        new Tarifs(2000, 3000);
    }

    public function testUnTarifNegatifEstRefuse(): void
    {
        $this->expectException(TarifInvalideException::class);

        new Tarifs(-1, 0);
    }
}
