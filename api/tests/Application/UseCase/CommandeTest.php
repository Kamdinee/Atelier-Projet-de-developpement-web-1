<?php

declare(strict_types=1);

namespace Nrv\Tests\Application\UseCase;

use InvalidArgumentException;
use Nrv\Application\UseCase\ConsulterCommande;
use Nrv\Application\UseCase\CreerCommande;
use Nrv\Application\UseCase\PayerCommande;
use Nrv\Domain\Exception\PlacesInsuffisantesException;
use Nrv\Domain\ValueObject\Uuid;
use Nrv\Tests\Application\Mock\InMemoryCommandeRepository;
use Nrv\Tests\Application\Mock\InMemoryPassRepository;
use Nrv\Tests\Application\Mock\InMemorySoireeRepository;
use Nrv\Tests\Fixtures\Fabrique;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class CommandeTest extends TestCase
{
    private InMemoryCommandeRepository $commandeRepo;
    private InMemorySoireeRepository $soireeRepo;
    private InMemoryPassRepository $passRepo;
    private CreerCommande $creerCommande;
    private PayerCommande $payerCommande;
    private ConsulterCommande $consulterCommande;

    protected function setUp(): void
    {
        $this->commandeRepo = new InMemoryCommandeRepository();

        $soiree1 = Fabrique::soiree(id: 1, jauge: 100, placesVendues: 20);
        $soiree2 = Fabrique::soiree(id: 2, jauge: 100, placesVendues: 50);
        $soiree3 = Fabrique::soiree(id: 3, jauge: 100, placesVendues: 10);

        $this->soireeRepo = new InMemorySoireeRepository([$soiree1, $soiree2, $soiree3]);

        $this->passRepo = new InMemoryPassRepository([
            [
                'id' => 1,
                'libelle' => 'Pass 3 jours',
                'nb_soirees' => 3,
                'tarif_normal' => 7000,
                'tarif_reduit' => 5000,
            ],
        ]);

        $this->creerCommande = new CreerCommande(
            $this->commandeRepo,
            $this->soireeRepo,
            $this->passRepo,
        );

        $this->payerCommande = new PayerCommande(
            $this->commandeRepo,
            $this->soireeRepo,
        );

        $this->consulterCommande = new ConsulterCommande(
            $this->commandeRepo,
        );
    }

    public function testUuidFormat(): void
    {
        $uuid = Uuid::v4();
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $uuid,
        );
    }

    public function testCreerCommandeAvecSoiree(): void
    {
        $commande = $this->creerCommande->executer(42, [
            [
                'type' => 'SOIREE',
                'soiree_id' => 1,
                'categorie' => 'NORMAL',
                'quantite' => 2,
            ],
        ]);

        $this->assertNotNull($commande);
        $this->assertSame(42, $commande['utilisateur_id']);
        $this->assertSame('EN_ATTENTE', $commande['statut']);
        $this->assertSame(6000, $commande['total']); // 2 * 3000
        $this->assertCount(1, $commande['lignes']);
    }

    public function testCreerCommandePanierVideLeveException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->creerCommande->executer(42, []);
    }

    public function testCreerCommandePlacesInsuffisantesLeveException(): void
    {
        $this->expectException(PlacesInsuffisantesException::class);

        // Soiree avec 1 place restante
        $soireeSaturee = Fabrique::soiree(id: 99, jauge: 10, placesVendues: 9);
        $soireeRepo = new InMemorySoireeRepository([$soireeSaturee]);
        $creer = new CreerCommande($this->commandeRepo, $soireeRepo, $this->passRepo);

        $creer->executer(42, [
            [
                'type' => 'SOIREE',
                'soiree_id' => 99,
                'categorie' => 'NORMAL',
                'quantite' => 2,
            ],
        ]);
    }

    public function testPaiementSimuleEtEmissionBillets(): void
    {
        $commande = $this->creerCommande->executer(42, [
            [
                'type' => 'SOIREE',
                'soiree_id' => 1,
                'categorie' => 'NORMAL',
                'quantite' => 2,
            ],
        ]);

        $res = $this->payerCommande->executer(42, $commande['id'], [
            'nom_carte' => 'Saad Moubine',
            'numero_carte' => '4970 1234 5678 4242',
            'date_expiration' => '12/28',
            'cvv' => '123',
        ]);

        $this->assertSame('PAYEE', $res['statut']);
        $this->assertSame('4242', $res['carte_fin']);
        $this->assertCount(2, $res['billets']);
        $this->assertSame('Saad Moubine', $res['billets'][0]['nom_acheteur']);
        $this->assertStringStartsWith('BIL-', $res['billets'][0]['reference']);
    }

    public function testPaiementParAutreUtilisateurRefuse(): void
    {
        $commande = $this->creerCommande->executer(42, [
            [
                'type' => 'SOIREE',
                'soiree_id' => 1,
                'categorie' => 'NORMAL',
                'quantite' => 1,
            ],
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('autorisé');

        $this->payerCommande->executer(999, $commande['id'], [
            'nom_carte' => 'Imposteur',
            'numero_carte' => '4970123456784242',
            'date_expiration' => '12/28',
            'cvv' => '123',
        ]);
    }

    public function testConsulterCommande(): void
    {
        $commande = $this->creerCommande->executer(42, [
            [
                'type' => 'SOIREE',
                'soiree_id' => 1,
                'categorie' => 'REDUIT',
                'quantite' => 1,
            ],
        ]);

        $consultee = $this->consulterCommande->executer(42, $commande['id']);
        $this->assertSame($commande['id'], $consultee['id']);
        $this->assertSame(2000, $consultee['total']);
    }
}
