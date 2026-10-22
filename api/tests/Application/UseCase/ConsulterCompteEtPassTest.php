<?php

declare(strict_types=1);

namespace Nrv\Tests\Application\UseCase;

use Nrv\Application\UseCase\ConsulterCompte;
use Nrv\Application\UseCase\ConsulterPass;
use Nrv\Application\UseCase\ListerSoirees;
use Nrv\Domain\Exception\UtilisateurIntrouvableException;
use Nrv\Tests\Application\Mock\InMemoryPassRepository;
use Nrv\Tests\Application\Mock\InMemorySoireeRepository;
use Nrv\Tests\Application\Mock\InMemoryUtilisateurRepository;
use Nrv\Tests\Fixtures\Fabrique;
use PHPUnit\Framework\TestCase;

final class ConsulterCompteEtPassTest extends TestCase
{
    public function testLeCompteNeContientPasLeMotDePasse(): void
    {
        $compte = (new ConsulterCompte(new InMemoryUtilisateurRepository([Fabrique::utilisateur(1)])))->executer(1);

        $this->assertSame('Marc', $compte->prenom);
        $this->assertArrayNotHasKey('motDePasseHache', get_object_vars($compte));
    }

    public function testUnCompteSupprimeLeveUneException(): void
    {
        $this->expectException(UtilisateurIntrouvableException::class);

        (new ConsulterCompte(new InMemoryUtilisateurRepository()))->executer(9);
    }

    public function testLesTarifsDuPass(): void
    {
        $pass = (new ConsulterPass(new InMemoryPassRepository([Fabrique::pass()])))->executer();

        $this->assertCount(1, $pass);
        $this->assertSame(3, $pass[0]->nbSoirees);
        $this->assertSame(8730, $pass[0]->tarifNormal);
        $this->assertSame(5820, $pass[0]->tarifReduit);
    }

    public function testLaListeDesSoireesDonneLesPlacesRestantes(): void
    {
        $soirees = (new ListerSoirees(new InMemorySoireeRepository([
            Fabrique::soiree(id: 1, jauge: 100, placesVendues: 98),
            Fabrique::soiree(id: 2, jauge: 100, placesVendues: 100),
        ])))->executer();

        $this->assertCount(2, $soirees);
        $this->assertSame(2, $soirees[0]->placesRestantes);
        $this->assertTrue($soirees[1]->complete);
    }
}
