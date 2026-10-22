<?php

declare(strict_types=1);

namespace Nrv\Tests\Domain\Entity;

use Nrv\Domain\Entity\Utilisateur;
use Nrv\Domain\Exception\DonneeInvalideException;
use Nrv\Domain\ValueObject\Role;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class UtilisateurTest extends TestCase
{
    public function testEmailMisEnMinusculesEtChampsNettoyes(): void
    {
        $u = new Utilisateur(null, '  Lea.Martin@NRV.net ', ' Martin ', ' Léa ', 'hache');

        $this->assertSame('lea.martin@nrv.net', $u->email());
        $this->assertSame('Martin', $u->nom());
        $this->assertSame('Léa', $u->prenom());
        $this->assertNull($u->id());
    }

    public function testUnNouveauCompteEstClientParDefaut(): void
    {
        $u = new Utilisateur(null, 'lea@nrv.net', 'Martin', 'Léa', 'hache');

        $this->assertSame(Role::Client, $u->role());
        $this->assertFalse($u->estAdmin());
    }

    public function testUnAdministrateurEstReconnu(): void
    {
        $this->assertTrue((new Utilisateur(1, 'admin@nrv.net', 'B', 'S', 'hache', Role::Admin))->estAdmin());
    }

    public function testAvecIdGardeLesDonnees(): void
    {
        $u = (new Utilisateur(null, 'lea@nrv.net', 'Martin', 'Léa', 'hache'))->avecId(7);

        $this->assertSame(7, $u->id());
        $this->assertSame('lea@nrv.net', $u->email());
        $this->assertSame('hache', $u->motDePasseHache());
    }

    /** @return array<string, array{string, string, string, string}> */
    public static function comptesInvalides(): array
    {
        return [
            'email sans arobase' => ['lea.nrv.net', 'Martin', 'Léa', 'hache'],
            'email trop long' => [str_repeat('a', 170) . '@nrv.net.fr', 'Martin', 'Léa', 'hache'],
            'nom vide' => ['lea@nrv.net', '   ', 'Léa', 'hache'],
            'prenom trop long' => ['lea@nrv.net', 'Martin', str_repeat('é', 81), 'hache'],
            'sans mot de passe' => ['lea@nrv.net', 'Martin', 'Léa', ''],
        ];
    }

    #[DataProvider('comptesInvalides')]
    public function testUnCompteInvalideEstRefuse(string $email, string $nom, string $prenom, string $hache): void
    {
        $this->expectException(DonneeInvalideException::class);

        new Utilisateur(null, $email, $nom, $prenom, $hache);
    }
}
