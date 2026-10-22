<?php

declare(strict_types=1);

namespace Nrv\Tests\Application\UseCase;

use DateTimeImmutable;
use Nrv\Application\UseCase\ConsulterMesAchats;
use Nrv\Domain\Exception\CommandeIntrouvableException;
use Nrv\Domain\ValueObject\CommandeResume;
use Nrv\Tests\Application\Mock\InMemoryBilletRepository;
use Nrv\Tests\Application\Mock\InMemoryCommandeLectureRepository;
use Nrv\Tests\Fixtures\Fabrique;
use PHPUnit\Framework\TestCase;

final class ConsulterMesAchatsTest extends TestCase
{
    private const MARC = 2;
    private const AUTRE = 3;
    private const COMMANDE = 'c0000000-0000-4000-8000-000000000001';

    private function useCase(): ConsulterMesAchats
    {
        $commande = new CommandeResume(self::COMMANDE, 'PAYEE', 6000, 2, new DateTimeImmutable('2026-10-15'), new DateTimeImmutable('2026-10-15'));

        return new ConsulterMesAchats(
            new InMemoryCommandeLectureRepository([self::MARC => [$commande]]),
            new InMemoryBilletRepository(
                [Fabrique::billet(), Fabrique::billet(jeton: '1c7e4b20-9d3a-4f8e-b6a1-0e5f2c8d7a44', reference: 'NRV-C0000000-02')],
                [self::COMMANDE => self::MARC],
            ),
        );
    }

    public function testListeLesCommandesDeLUtilisateur(): void
    {
        $commandes = $this->useCase()->commandes(self::MARC);

        $this->assertCount(1, $commandes);
        $this->assertSame('PAYEE', $commandes[0]->statut);
        $this->assertSame(2, $commandes[0]->nombreBillets);
        $this->assertSame([], $this->useCase()->commandes(self::AUTRE));
    }

    public function testRenvoieLesBilletsDUneDeMesCommandes(): void
    {
        $billets = $this->useCase()->billetsDeCommande(self::MARC, self::COMMANDE);

        $this->assertCount(2, $billets);
        $this->assertSame('NRV-C0000000-02', $billets[1]->reference);
    }

    public function testLaCommandeDUnAutreClientEstIntrouvable(): void
    {
        $this->expectException(CommandeIntrouvableException::class);

        $this->useCase()->billetsDeCommande(self::AUTRE, self::COMMANDE);
    }

    public function testTousMesBillets(): void
    {
        $this->assertCount(2, $this->useCase()->billets(self::MARC));
        $this->assertCount(0, $this->useCase()->billets(self::AUTRE));
    }
}
