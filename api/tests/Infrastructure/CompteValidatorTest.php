<?php

declare(strict_types=1);

namespace Nrv\Tests\Infrastructure;

use Nrv\Infrastructure\Http\Exception\RequeteInvalideException;
use Nrv\Infrastructure\Http\Validation\CompteValidator;
use Nrv\Infrastructure\Security\BcryptHacheur;
use PHPUnit\Framework\TestCase;

final class CompteValidatorTest extends TestCase
{
    public function testUneInscriptionCorrecteEstNettoyee(): void
    {
        $d = (new CompteValidator())->inscription([
            'email' => ' lea@nrv.net ', 'nom' => ' Martin ', 'prenom' => 'Léa', 'mot_de_passe' => 'motdepasse1',
        ]);

        $this->assertSame(['email' => 'lea@nrv.net', 'nom' => 'Martin', 'prenom' => 'Léa', 'mot_de_passe' => 'motdepasse1'], $d);
    }

    public function testChaqueChampFauxASonMessage(): void
    {
        try {
            (new CompteValidator())->inscription(['email' => 'x', 'nom' => ['tableau'], 'mot_de_passe' => 'court']);
            $this->fail('exception attendue');
        } catch (RequeteInvalideException $e) {
            $this->assertSame(['email', 'nom', 'prenom', 'mot_de_passe'], array_keys($e->erreurs));
        }
    }

    public function testUnCorpsQuiNEstPasUnObjetJsonEstRefuse(): void
    {
        $this->expectException(RequeteInvalideException::class);

        (new CompteValidator())->connexion(null);
    }

    public function testLaConnexionDemandeEmailEtMotDePasse(): void
    {
        try {
            (new CompteValidator())->connexion(['email' => '', 'mot_de_passe' => 42]);
            $this->fail('exception attendue');
        } catch (RequeteInvalideException $e) {
            $this->assertSame(['email', 'mot_de_passe'], array_keys($e->erreurs));
        }
    }

    public function testBcryptSaleChaqueHacheDifferemment(): void
    {
        $hacheur = new BcryptHacheur();
        $a = $hacheur->hacher('motdepasse1');

        $this->assertStringStartsWith('$2y$12$', $a);
        $this->assertNotSame($a, $hacheur->hacher('motdepasse1'));
        $this->assertTrue($hacheur->verifier('motdepasse1', $a));
        $this->assertFalse($hacheur->verifier('motdepasse2', $a));
    }
}
