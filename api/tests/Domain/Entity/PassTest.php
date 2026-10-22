<?php

declare(strict_types=1);

namespace Nrv\Tests\Domain\Entity;

use Nrv\Domain\Entity\Pass;
use Nrv\Domain\Exception\DonneeInvalideException;
use Nrv\Domain\ValueObject\CategorieTarif;
use Nrv\Domain\ValueObject\Tarifs;
use Nrv\Tests\Fixtures\Fabrique;
use PHPUnit\Framework\TestCase;

final class PassTest extends TestCase
{
    public function testLePass3JoursATroisSoireesEtDeuxTarifs(): void
    {
        $pass = Fabrique::pass();

        $this->assertSame(3, $pass->nbSoirees());
        $this->assertSame(8730, $pass->tarifs()->pour(CategorieTarif::Normal));
        $this->assertSame(5820, $pass->tarifs()->pour(CategorieTarif::Reduit));
    }

    public function testTroisSoireesDistinctesSontAcceptees(): void
    {
        Fabrique::pass()->verifierSoirees([4, 8, 11]);

        $this->addToAssertionCount(1);
    }

    public function testDeuxFoisLaMemeSoireeEstRefusee(): void
    {
        $this->expectException(DonneeInvalideException::class);

        Fabrique::pass()->verifierSoirees([4, 4, 11]);
    }

    public function testDeuxSoireesNeSuffisentPas(): void
    {
        $this->expectException(DonneeInvalideException::class);

        Fabrique::pass()->verifierSoirees([4, 8]);
    }

    public function testUnPassSurUneSeuleSoireeNExistePas(): void
    {
        $this->expectException(DonneeInvalideException::class);

        new Pass(2, 'Pass 1 jour', 1, new Tarifs(3000, 2000));
    }
}
