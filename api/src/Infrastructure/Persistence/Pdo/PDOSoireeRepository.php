<?php

declare(strict_types=1);

namespace Nrv\Infrastructure\Persistence\Pdo;

use DateTimeImmutable;
use Nrv\Application\Port\Out\SoireeRepositoryInterface;
use Nrv\Domain\Entity\Lieu;
use Nrv\Domain\Entity\Soiree;
use Nrv\Domain\ValueObject\Tarifs;
use PDO;

// adaptateur pdo du port SoireeRepositoryInterface
final class PDOSoireeRepository implements SoireeRepositoryInterface
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly SpectacleHydrator $hydrator,
    ) {
    }

    public function trouverParId(int $id): ?Soiree
    {
        // la soiree et son lieu
        $requete = $this->pdo->prepare(
            'SELECT s.id, s.nom, s.thematique, s.date_soiree, s.heure_debut, s.jauge, s.places_vendues,
                s.tarif_normal, s.tarif_reduit, l.id AS lieu_id, l.nom AS lieu_nom, l.adresse AS lieu_adresse,
                l.capacite AS lieu_capacite
            FROM soirees s
            JOIN lieux l ON l.id = s.lieu_id
            WHERE s.id = :id'
        );
        $requete->bindValue('id', $id, PDO::PARAM_INT);
        $requete->execute();

        $l = $requete->fetch(PDO::FETCH_ASSOC);
        if ($l === false) {
            return null;
        }

        // ses spectacles
        $spectacles = $this->pdo->prepare(
            SpectacleHydrator::SELECT . ' WHERE sp.soiree_id = :id ORDER BY sp.horaire_previsionnel'
        );
        $spectacles->bindValue('id', $id, PDO::PARAM_INT);
        $spectacles->execute();

        return new Soiree(
            (int) $l['id'],
            (string) $l['nom'],
            (string) $l['thematique'],
            new DateTimeImmutable($l['date_soiree'] . ' ' . $l['heure_debut']),
            new Lieu((int) $l['lieu_id'], (string) $l['lieu_nom'], (string) $l['lieu_adresse'], (int) $l['lieu_capacite']),
            new Tarifs((int) $l['tarif_normal'], (int) $l['tarif_reduit']),
            (int) $l['jauge'],
            (int) $l['places_vendues'],
            $this->hydrator->hydrater($spectacles->fetchAll(PDO::FETCH_ASSOC)),
        );
    }
}
