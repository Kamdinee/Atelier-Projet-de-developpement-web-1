<?php

declare(strict_types=1);

namespace Nrv\Tests\Infrastructure\Validation;

use Nrv\Infrastructure\Http\Exception\RequeteInvalideException;
use Nrv\Infrastructure\Http\Validation\ProgrammeAdminValidator;
use PHPUnit\Framework\TestCase;

final class ProgrammeAdminValidatorTest extends TestCase
{
    private function spectacle(array $modif = []): array
    {
        return $modif + [
            'titre' => 'Iron Lorraine',
            'description' => 'Riffs massifs.',
            'style' => 'metal',
            'artistes' => 'Iron Lorraine, Invité',
            'horaire' => '20:30',
            'video' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
            'image' => '',
        ];
    }

    private function soiree(array $modif = []): array
    {
        return $modif + [
            'nom' => 'Nuit bonus',
            'thematique' => 'Soirée métal',
            'date' => '2027-06-18',
            'heure_debut' => '19:30',
            'lieu_id' => '3',
            'jauge' => '',
            'tarif_normal' => 3000,
            'tarif_reduit' => 2000,
            'spectacles' => [$this->spectacle(), $this->spectacle(['titre' => 'Fer Rouge'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function erreurs(callable $appel): array
    {
        try {
            $appel();
        } catch (RequeteInvalideException $e) {
            return $e->erreurs;
        }
        $this->fail('RequeteInvalideException attendue');
    }

    public function testUneSoireeCorrecteDonneLeDto(): void
    {
        $dto = (new ProgrammeAdminValidator())->soiree($this->soiree());

        $this->assertSame(3, $dto->lieuId);
        $this->assertNull($dto->jauge);
        $this->assertCount(2, $dto->spectacles);
        $this->assertSame(['Iron Lorraine', 'Invité'], $dto->spectacles[0]->artistes);
        $this->assertNull($dto->spectacles[0]->image);
    }

    public function testSignaleChaqueChampInvalide(): void
    {
        $v = new ProgrammeAdminValidator();
        $erreurs = $this->erreurs(fn () => $v->soiree($this->soiree([
            'nom' => '',
            'date' => '2027-02-31',
            'heure_debut' => '25:00',
            'tarif_normal' => -5,
            'spectacles' => [$this->spectacle(['style' => "metal' OR 1=1", 'video' => 'javascript:alert(1)']), $this->spectacle(['artistes' => []])],
        ])));

        foreach (['nom', 'date', 'heure_debut', 'tarif_normal', 'spectacles.0.style', 'spectacles.0.video', 'spectacles.1.artistes'] as $champ) {
            $this->assertArrayHasKey($champ, $erreurs, $champ);
        }
    }

    public function testIlFautDeDeuxATroisSpectacles(): void
    {
        $v = new ProgrammeAdminValidator();

        $this->assertArrayHasKey('spectacles', $this->erreurs(fn () => $v->soiree($this->soiree(['spectacles' => [$this->spectacle()]]))));
    }

    public function testUneImageDoitEtreHttpsOuLocale(): void
    {
        $v = new ProgrammeAdminValidator();

        $this->assertSame('img/spectacles/metal-1.webp', $v->spectacle($this->spectacle(['image' => 'img/spectacles/metal-1.webp']))->image);
        $this->assertArrayHasKey('image', $this->erreurs(fn () => $v->spectacle($this->spectacle(['image' => 'http://pirate.example/x.png']))));
    }

    public function testUnCorpsQuiNestPasUnObjetEstRefuse(): void
    {
        $v = new ProgrammeAdminValidator();

        $this->assertArrayHasKey('corps', $this->erreurs(fn () => $v->soiree(null)));
    }
}
