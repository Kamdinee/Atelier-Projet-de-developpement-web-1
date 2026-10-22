<?php

declare(strict_types=1);

namespace Nrv\Tests\Application\UseCase;

use Nrv\Application\UseCase\AuthentifierUtilisateur;
use Nrv\Domain\Exception\IdentifiantsInvalidesException;
use Nrv\Domain\ValueObject\Role;
use Nrv\Tests\Application\Mock\FauxHacheur;
use Nrv\Tests\Application\Mock\FauxJeton;
use Nrv\Tests\Application\Mock\InMemoryUtilisateurRepository;
use Nrv\Tests\Fixtures\Fabrique;
use PHPUnit\Framework\TestCase;

final class AuthentifierUtilisateurTest extends TestCase
{
    private AuthentifierUtilisateur $useCase;

    protected function setUp(): void
    {
        $this->useCase = new AuthentifierUtilisateur(
            new InMemoryUtilisateurRepository([
                Fabrique::utilisateur(1),
                Fabrique::utilisateur(2, Role::Admin, 'admin@nrv.net'),
            ]),
            new FauxHacheur(),
            new FauxJeton(),
        );
    }

    public function testDeBonsIdentifiantsDonnentUnJetonEtLeCompte(): void
    {
        $session = $this->useCase->executer(' Client@NRV.net ', 'client12345');

        $this->assertSame('jeton-1-CLIENT', $session->jeton);
        $this->assertSame('2027-06-04T21:00:00+00:00', $session->expireLe);
        $this->assertSame('client@nrv.net', $session->utilisateur->email);
    }

    public function testLeJetonPorteLeRoleAdministrateur(): void
    {
        $this->assertSame('jeton-2-ADMIN', $this->useCase->executer('admin@nrv.net', 'client12345')->jeton);
    }

    public function testUnMauvaisMotDePasseEstRefuse(): void
    {
        $this->expectException(IdentifiantsInvalidesException::class);

        $this->useCase->executer('client@nrv.net', 'mauvais');
    }

    public function testUnEmailInconnuDonneLaMemeErreur(): void
    {
        $this->expectException(IdentifiantsInvalidesException::class);

        $this->useCase->executer('inconnu@nrv.net', 'client12345');
    }
}
