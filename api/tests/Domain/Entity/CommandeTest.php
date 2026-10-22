<?php

declare(strict_types=1);

namespace Nrv\Tests\Domain\Entity;

use DateTimeImmutable;
use Nrv\Domain\Entity\Commande;
use Nrv\Domain\Exception\CommandeDejaPayeeException;
use Nrv\Domain\Exception\DonneeInvalideException;
use Nrv\Domain\Exception\SoireeCompleteException;
use Nrv\Domain\ValueObject\CarteBancaire;
use Nrv\Domain\ValueObject\CategorieTarif;
use Nrv\Domain\ValueObject\JetonBillet;
use Nrv\Domain\ValueObject\LigneCommande;
use Nrv\Domain\ValueObject\StatutCommande;
use Nrv\Domain\ValueObject\TypeBillet;
use Nrv\Tests\Fixtures\Fabrique;
use PHPUnit\Framework\TestCase;

final class CommandeTest extends TestCase
{
    private const ID = '1a2b3c4d-0000-4000-8000-000000000001';

    private function commande(): Commande
    {
        return new Commande(self::ID, 2, [
            LigneCommande::pourSoiree(Fabrique::soiree(id: 8), CategorieTarif::Normal, 2),
            LigneCommande::pourPass(Fabrique::pass(), [Fabrique::soiree(id: 4), Fabrique::soiree(id: 8), Fabrique::soiree(id: 11)], CategorieTarif::Reduit, 1),
        ], new DateTimeImmutable('2026-10-22 20:00'));
    }

    private function carte(): CarteBancaire
    {
        return new CarteBancaire('4242 4242 4242 4242', 12, 2028, '123');
    }

    public function testLePrixVientDeLaSoireeEtDuPass(): void
    {
        $c = $this->commande();

        $this->assertSame(3000, $c->lignes()[0]->prixUnitaire());
        $this->assertSame(5820, $c->lignes()[1]->prixUnitaire());
        $this->assertSame(2 * 3000 + 5820, $c->total());
        $this->assertSame(3, $c->nombreBillets());
        $this->assertSame(StatutCommande::EnAttente, $c->statut());
    }

    public function testUnPassPrendUnePlaceDansChacuneDeSesSoirees(): void
    {
        $this->assertSame([4 => 1, 8 => 3, 11 => 1], $this->commande()->placesParSoiree());
    }

    public function testLePaiementGardeSeulementLaFinDeLaCarte(): void
    {
        $c = $this->commande();
        $c->payer($this->carte(), new DateTimeImmutable('2026-10-22 20:05'));

        $this->assertSame(StatutCommande::Payee, $c->statut());
        $this->assertSame('4242', $c->carteFin());
    }

    public function testUneCommandeNeSePayeQuUneFois(): void
    {
        $c = $this->commande();
        $c->payer($this->carte(), new DateTimeImmutable());

        $this->expectException(CommandeDejaPayeeException::class);

        $c->payer($this->carte(), new DateTimeImmutable());
    }

    public function testUneCarteExpireeEstRefusee(): void
    {
        $this->expectException(DonneeInvalideException::class);

        $this->commande()->payer(new CarteBancaire('4242424242424242', 9, 2026, '123'), new DateTimeImmutable('2026-10-22'));
    }

    public function testUnBilletParPlaceAvecUneReferenceEtUnJetonChacun(): void
    {
        $c = $this->commande();
        $c->payer($this->carte(), new DateTimeImmutable());
        $n = 0;
        $billets = $c->billetsAEmettre('Marc Durand', static function () use (&$n): JetonBillet {
            return new JetonBillet(sprintf('00000000-0000-4000-8000-%012d', ++$n));
        });

        $this->assertCount(3, $billets);
        $this->assertSame(['NRV-1A2B3C4D-01', 'NRV-1A2B3C4D-02', 'NRV-1A2B3C4D-03'], array_map(static fn ($b) => $b->reference, $billets));
        $this->assertSame(TypeBillet::Pass, $billets[2]->type);
        $this->assertSame([4, 8, 11], $billets[2]->soireeIds);
        $this->assertCount(3, array_unique(array_map(static fn ($b) => $b->jeton->valeur(), $billets)));
    }

    public function testPasDeBilletAvantLePaiement(): void
    {
        $this->expectException(DonneeInvalideException::class);

        $this->commande()->billetsAEmettre('Marc Durand', static fn () => new JetonBillet('00000000-0000-4000-8000-000000000001'));
    }

    public function testUneSoireeCompleteNePeutPasEtreCommandee(): void
    {
        $this->expectException(SoireeCompleteException::class);

        LigneCommande::pourSoiree(Fabrique::soiree(id: 3, jauge: 100, placesVendues: 100), CategorieTarif::Normal, 1);
    }

    public function testUnPassSurDeuxFoisLaMemeSoireeEstRefuse(): void
    {
        $this->expectException(DonneeInvalideException::class);

        LigneCommande::pourPass(Fabrique::pass(), [Fabrique::soiree(id: 4), Fabrique::soiree(id: 4), Fabrique::soiree(id: 8)], CategorieTarif::Normal, 1);
    }

    public function testPlusDe10BilletsParLigneSontRefuses(): void
    {
        $this->expectException(DonneeInvalideException::class);

        LigneCommande::pourSoiree(Fabrique::soiree(id: 8), CategorieTarif::Normal, 11);
    }

    public function testUneCommandeVideEstRefusee(): void
    {
        $this->expectException(DonneeInvalideException::class);

        new Commande(self::ID, 2, [], new DateTimeImmutable());
    }
}
