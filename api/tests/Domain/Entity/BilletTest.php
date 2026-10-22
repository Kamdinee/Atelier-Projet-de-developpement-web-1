<?php

declare(strict_types=1);

namespace Nrv\Tests\Domain\Entity;

use Nrv\Domain\Exception\DonneeInvalideException;
use Nrv\Domain\ValueObject\TypeBillet;
use Nrv\Tests\Fixtures\Fabrique;
use PHPUnit\Framework\TestCase;

final class BilletTest extends TestCase
{
    public function testUnBilletSoireeDonneAccesAUneSoiree(): void
    {
        $billet = Fabrique::billet();

        $this->assertFalse($billet->estUnPass());
        $this->assertCount(1, $billet->soirees());
        $this->assertSame(8, $billet->soirees()[0]->id());
    }

    public function testUnPassDonneAccesATroisSoireesTrieesParDate(): void
    {
        $billet = Fabrique::billet(TypeBillet::Pass);

        $this->assertTrue($billet->estUnPass());
        $ids = array_map(static fn ($s) => $s->id(), $billet->soirees());
        $this->assertSame([8, 12, 15], $ids);
    }

    public function testUnPassDeDeuxSoireesEstRefuse(): void
    {
        $this->expectException(DonneeInvalideException::class);

        Fabrique::billet(TypeBillet::Pass, [Fabrique::soireeBillet(8), Fabrique::soireeBillet(9, '2027-06-12 19:00')]);
    }

    public function testUnPassAvecDeuxFoisLaMemeSoireeEstRefuse(): void
    {
        $this->expectException(DonneeInvalideException::class);

        Fabrique::billet(TypeBillet::Pass, [Fabrique::soireeBillet(8), Fabrique::soireeBillet(8), Fabrique::soireeBillet(9)]);
    }

    public function testUnBilletSoireeAvecDeuxSoireesEstRefuse(): void
    {
        $this->expectException(DonneeInvalideException::class);

        Fabrique::billet(TypeBillet::Soiree, [Fabrique::soireeBillet(8), Fabrique::soireeBillet(9)]);
    }

    public function testUnJetonMalFormeEstRefuse(): void
    {
        $this->expectException(DonneeInvalideException::class);

        Fabrique::billet(jeton: 'pas-un-uuid');
    }
}
