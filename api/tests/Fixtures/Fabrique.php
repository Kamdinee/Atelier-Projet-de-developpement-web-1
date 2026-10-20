<?php

declare(strict_types=1);

namespace Nrv\Tests\Fixtures;

use DateTimeImmutable;
use Nrv\Domain\Entity\Lieu;
use Nrv\Domain\Entity\Soiree;
use Nrv\Domain\Entity\Spectacle;
use Nrv\Domain\Entity\Style;
use Nrv\Domain\ValueObject\Image;
use Nrv\Domain\ValueObject\Tarifs;

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
}
