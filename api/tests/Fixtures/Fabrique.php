<?php

declare(strict_types=1);

namespace Nrv\Tests\Fixtures;

use DateTimeImmutable;
use Nrv\Domain\Entity\Billet;
use Nrv\Domain\Entity\Lieu;
use Nrv\Domain\Entity\Pass;
use Nrv\Domain\Entity\Soiree;
use Nrv\Domain\Entity\Spectacle;
use Nrv\Domain\Entity\Style;
use Nrv\Domain\Entity\Utilisateur;
use Nrv\Domain\ValueObject\CategorieTarif;
use Nrv\Domain\ValueObject\Image;
use Nrv\Domain\ValueObject\JetonBillet;
use Nrv\Domain\ValueObject\Role;
use Nrv\Domain\ValueObject\SoireeBillet;
use Nrv\Domain\ValueObject\Tarifs;
use Nrv\Domain\ValueObject\TypeBillet;

// fabrique des objets du domaine pour les tests
final class Fabrique
{
    public static function style(string $slug = 'metal', int $id = 1): Style
    {
        return new Style($id, ucfirst($slug), $slug);
    }

    public static function lieu(int $capacite = 100): Lieu
    {
        return new Lieu(1, 'Zénith de Nancy', 'rue du Zénith, Maxéville', $capacite);
    }

    public static function spectacle(
        int $id = 1,
        int $soireeId = 1,
        string $horaire = '2027-06-11 20:00',
        ?Style $style = null,
    ): Spectacle {
        return new Spectacle(
            $id,
            $soireeId,
            "Spectacle {$id}",
            'description',
            $style ?? self::style(),
            ["Artiste {$id}"],
            [new Image("https://exemple.fr/{$id}.jpg", 'photo')],
            null,
            new DateTimeImmutable($horaire),
        );
    }

    /**
     * @param list<Spectacle>|null $spectacles
     */
    public static function soiree(
        int $id = 1,
        int $jauge = 100,
        int $placesVendues = 0,
        ?array $spectacles = null,
        int $capacite = 100,
    ): Soiree {
        return new Soiree(
            $id,
            'Nuit métal',
            'Soirée métal',
            new DateTimeImmutable('2027-06-11 19:30'),
            self::lieu($capacite),
            new Tarifs(3000, 2000),
            $jauge,
            $placesVendues,
            $spectacles ?? [
                self::spectacle(1, $id, '2027-06-11 20:00'),
                self::spectacle(2, $id, '2027-06-11 21:30'),
            ],
        );
    }

    // mot de passe "client12345" (hache par FauxHacheur)
    public static function utilisateur(int $id = 1, Role $role = Role::Client, string $email = 'client@nrv.net'): Utilisateur
    {
        return new Utilisateur($id, $email, 'Durand', 'Marc', 'hache:client12345', $role);
    }

    public static function pass(): Pass
    {
        return new Pass(1, 'Pass 3 jours', 3, new Tarifs(8730, 5820));
    }

    public const JETON = '9f1c2d7a-5e3b-4f60-8c0a-6b2e4d1f9a33';

    public static function soireeBillet(int $id = 8, string $debut = '2027-06-11 19:30'): SoireeBillet
    {
        return new SoireeBillet($id, "Soirée {$id}", new DateTimeImmutable($debut), 'Zénith de Nancy', 'rue du Zénith', [
            ['titre' => 'Abyssal Forge', 'horaire' => '20:00'],
        ]);
    }

    /**
     * @param list<SoireeBillet>|null $soirees
     */
    public static function billet(
        TypeBillet $type = TypeBillet::Soiree,
        ?array $soirees = null,
        string $jeton = self::JETON,
        string $commandeId = 'c0000000-0000-4000-8000-000000000001',
        string $reference = 'NRV-C0000000-01',
    ): Billet {
        return new Billet(
            1,
            $reference,
            new JetonBillet($jeton),
            $commandeId,
            $type,
            CategorieTarif::Normal,
            $type === TypeBillet::Pass ? 8730 : 3000,
            'Marc Durand',
            new DateTimeImmutable('2026-10-15 18:42'),
            $soirees ?? ($type === TypeBillet::Pass
                ? [self::soireeBillet(12, '2027-06-12 19:00'), self::soireeBillet(8), self::soireeBillet(15, '2027-06-15 20:00')]
                : [self::soireeBillet()]),
        );
    }
}
