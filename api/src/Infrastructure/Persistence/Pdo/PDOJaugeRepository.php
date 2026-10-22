<?php

declare(strict_types=1);

namespace Nrv\Infrastructure\Persistence\Pdo;

use DateTimeImmutable;
use Nrv\Application\Port\Out\JaugeRepositoryInterface;
use Nrv\Domain\ValueObject\JaugeSoiree;
use PDO;

// adaptateur pdo du port JaugeRepositoryInterface, lit la vue v_jauges_soirees (sql/01_schema.sql)
final class PDOJaugeRepository implements JaugeRepositoryInterface
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function toutes(): array
    {
        $requete = $this->pdo->prepare(
            'SELECT id, nom, date_soiree, heure_debut, lieu, jauge, places_vendues,
                billets_soiree, billets_pass, recette
            FROM v_jauges_soirees
            ORDER BY date_soiree'
        );
        $requete->execute();

        return array_map(
            static fn (array $l): JaugeSoiree => new JaugeSoiree(
                (int) $l['id'],
                (string) $l['nom'],
                new DateTimeImmutable($l['date_soiree'] . ' ' . $l['heure_debut']),
                (string) $l['lieu'],
                (int) $l['jauge'],
                (int) $l['places_vendues'],
                (int) $l['billets_soiree'],
                (int) $l['billets_pass'],
                (int) round((float) $l['recette']),
            ),
            $requete->fetchAll(PDO::FETCH_ASSOC),
        );
    }
}
