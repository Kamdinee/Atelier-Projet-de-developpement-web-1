<?php

declare(strict_types=1);

namespace Nrv\Tests\Application\UseCase;

use Nrv\Application\Dto\LigneDemandeeDto;
use Nrv\Application\UseCase\ConsulterCommande;
use Nrv\Application\UseCase\CreerCommande;
use Nrv\Application\UseCase\PayerCommande;
use Nrv\Domain\Exception\CommandeDejaPayeeException;
use Nrv\Domain\Exception\CommandeIntrouvableException;
use Nrv\Domain\Exception\PaiementRefuseException;
use Nrv\Domain\Exception\PlacesInsuffisantesException;
use Nrv\Domain\Exception\SoireeIntrouvableException;
use Nrv\Domain\ValueObject\CarteBancaire;
use Nrv\Domain\ValueObject\StatutCommande;
use Nrv\Tests\Application\Mock\FausseBanque;
use Nrv\Tests\Application\Mock\FausseTransaction;
use Nrv\Tests\Application\Mock\FauxGenerateurId;
use Nrv\Tests\Application\Mock\HorlogeFixe;
use Nrv\Tests\Application\Mock\InMemoryBilletRepository;
use Nrv\Tests\Application\Mock\InMemoryCommandeRepository;
use Nrv\Tests\Application\Mock\InMemoryPassRepository;
use Nrv\Tests\Application\Mock\InMemorySoireeRepository;
use Nrv\Tests\Application\Mock\InMemoryUtilisateurRepository;
use Nrv\Tests\Fixtures\Fabrique;
use PHPUnit\Framework\TestCase;

final class CommanderEtPayerTest extends TestCase
{
    private InMemorySoireeRepository $soirees;
    private InMemoryCommandeRepository $commandes;
    private FausseBanque $banque;
    private CreerCommande $creer;

    protected function setUp(): void
    {
        // soiree 11 : 2 places restantes ; soiree 3 : complete
        $this->soirees = new InMemorySoireeRepository([
            Fabrique::soiree(id: 4),
            Fabrique::soiree(id: 8),
            Fabrique::soiree(id: 11, jauge: 100, placesVendues: 98),
            Fabrique::soiree(id: 3, jauge: 100, placesVendues: 100),
        ]);
        $this->commandes = new InMemoryCommandeRepository();
        $this->banque = new FausseBanque();
        $this->creer = new CreerCommande($this->soirees, new InMemoryPassRepository([Fabrique::pass()]), $this->commandes, new FauxGenerateurId(), new HorlogeFixe());
    }

    private function payer(?FausseBanque $banque = null): PayerCommande
    {
        return new PayerCommande(
            new FausseTransaction(),
            $this->commandes,
            $this->soirees,
            new InMemoryUtilisateurRepository([Fabrique::utilisateur(2)]),
            new InMemoryBilletRepository([]),
            $banque ?? $this->banque,
            new FauxGenerateurId(),
            new HorlogeFixe(),
        );
    }

    private function carte(string $numero = '4242424242424242'): CarteBancaire
    {
        return new CarteBancaire($numero, 12, 2028, '123');
    }

    /** @return list<LigneDemandeeDto> */
    private function panier(int $billets11 = 1): array
    {
        return [
            new LigneDemandeeDto('SOIREE', [11], null, 'NORMAL', $billets11),
            new LigneDemandeeDto('PASS', [4, 8, 11], 1, 'REDUIT', 1),
        ];
    }

    public function testLaCommandeEstCreeeEnAttenteAvecLesPrixDuServeur(): void
    {
        $commande = $this->creer->executer(2, $this->panier());

        $this->assertSame('EN_ATTENTE', $commande->statut);
        $this->assertSame(3000 + 5820, $commande->total);
        $this->assertSame('Pass 3 jours', $commande->lignes[1]->libelle);
        $this->assertCount(1, $this->commandes->commandes);
    }

    public function testPlusDePlacesQueRestantesEstRefuseDesLaCommande(): void
    {
        $this->expectException(PlacesInsuffisantesException::class);

        // 2 billets + 1 pass sur la soiree 11 : 3 places pour 2 restantes
        $this->creer->executer(2, $this->panier(2));
    }

    public function testUneSoireeInconnueEstRefusee(): void
    {
        $this->expectException(SoireeIntrouvableException::class);

        $this->creer->executer(2, [new LigneDemandeeDto('SOIREE', [99], null, 'NORMAL', 1)]);
    }

    public function testLePaiementReserveLesPlacesEtEmetUnBilletParPlace(): void
    {
        $id = $this->creer->executer(2, $this->panier())->id;

        $paiement = $this->payer()->executer(2, $id, $this->carte());

        $this->assertSame('PAYEE', $paiement->commande->statut);
        $this->assertSame([8820], $this->banque->debits);
        $this->assertSame(0, $this->soirees->trouverParId(11)?->placesRestantes());
        $this->assertSame([4, 8, 11], $this->soirees->enregistrees);
        $this->assertCount(2, $this->commandes->billets);
        $this->assertSame('Marc Durand', $this->commandes->billets[0]->nomAcheteur);
    }

    public function testUneCarteRefuseeNEnregistreRien(): void
    {
        $id = $this->creer->executer(2, $this->panier())->id;

        try {
            $this->payer(new FausseBanque(refuser: true))->executer(2, $id, $this->carte());
            $this->fail('paiement refuse attendu');
        } catch (PaiementRefuseException) {
        }

        $this->assertSame(StatutCommande::EnAttente, $this->commandes->trouverParId($id)?->statut());
        $this->assertSame([], $this->soirees->enregistrees);
        $this->assertSame([], $this->commandes->billets);
    }

    public function testPlusAssezDePlacesAuPaiementRienNEstDebite(): void
    {
        $id = $this->creer->executer(2, $this->panier())->id;
        // entre-temps, un autre client achete les 2 dernieres places de la soiree 11
        $this->soirees->trouverParId(11)?->reserver(2);

        try {
            $this->payer()->executer(2, $id, $this->carte());
            $this->fail('places insuffisantes attendues');
        } catch (PlacesInsuffisantesException) {
        }

        $this->assertSame([], $this->banque->debits);
        $this->assertSame([], $this->commandes->billets);
    }

    public function testUnDeuxiemePaiementEstRefuse(): void
    {
        $id = $this->creer->executer(2, $this->panier())->id;
        $this->payer()->executer(2, $id, $this->carte());

        $this->expectException(CommandeDejaPayeeException::class);

        $this->payer()->executer(2, $id, $this->carte());
    }

    public function testOnNePeutPasPayerNiVoirLaCommandeDUnAutreClient(): void
    {
        $id = $this->creer->executer(2, $this->panier())->id;

        try {
            (new ConsulterCommande($this->commandes))->executer(7, $id);
            $this->fail('commande introuvable attendue');
        } catch (CommandeIntrouvableException) {
        }

        $this->expectException(CommandeIntrouvableException::class);

        $this->payer()->executer(7, $id, $this->carte());
    }
}
