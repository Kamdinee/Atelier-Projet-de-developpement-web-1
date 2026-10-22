<?php

declare(strict_types=1);

namespace Nrv\Tests\Application\UseCase;

use Nrv\Application\UseCase\InscrireUtilisateur;
use Nrv\Domain\Exception\DonneeInvalideException;
use Nrv\Domain\Exception\EmailDejaUtiliseException;
use Nrv\Tests\Application\Mock\FauxHacheur;
use Nrv\Tests\Application\Mock\InMemoryUtilisateurRepository;
use Nrv\Tests\Fixtures\Fabrique;
use PHPUnit\Framework\TestCase;

final class InscrireUtilisateurTest extends TestCase
{
    private InMemoryUtilisateurRepository $depot;
    private InscrireUtilisateur $useCase;

    protected function setUp(): void
    {
        $this->depot = new InMemoryUtilisateurRepository([Fabrique::utilisateur(1)]);
        $this->useCase = new InscrireUtilisateur($this->depot, new FauxHacheur());
    }

    public function testCreeUnCompteClientAvecLeMotDePasseHache(): void
    {
        $compte = $this->useCase->executer('Lea@nrv.net', 'Martin', 'Léa', 'motdepasse1');

        $this->assertSame(2, $compte->id);
        $this->assertSame('lea@nrv.net', $compte->email);
        $this->assertSame('CLIENT', $compte->role);
        $this->assertSame('hache:motdepasse1', $this->depot->trouverParId(2)?->motDePasseHache());
    }

    public function testUnEmailDejaUtiliseEstRefuseMemeAvecDesMajuscules(): void
    {
        $this->expectException(EmailDejaUtiliseException::class);

        $this->useCase->executer('CLIENT@nrv.net', 'Autre', 'Personne', 'motdepasse1');
    }

    public function testUnMotDePasseTropCourtEstRefuse(): void
    {
        $this->expectException(DonneeInvalideException::class);

        $this->useCase->executer('lea@nrv.net', 'Martin', 'Léa', 'court');
    }

    public function testUnMotDePasseDePlusDe72CaracteresEstRefuse(): void
    {
        $this->expectException(DonneeInvalideException::class);

        $this->useCase->executer('lea@nrv.net', 'Martin', 'Léa', str_repeat('a', 73));
    }

    public function testRienNEstEnregistreSiLesDonneesSontInvalides(): void
    {
        try {
            $this->useCase->executer('pas-un-email', 'Martin', 'Léa', 'motdepasse1');
        } catch (DonneeInvalideException) {
        }

        $this->assertCount(1, $this->depot->utilisateurs);
    }
}
