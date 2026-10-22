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

    private const SELECT = 'SELECT s.id, s.nom, s.thematique, s.date_soiree, s.heure_debut, s.jauge, s.places_vendues,
            s.tarif_normal, s.tarif_reduit, l.id AS lieu_id, l.nom AS lieu_nom, l.adresse AS lieu_adresse,
            l.capacite AS lieu_capacite
        FROM soirees s
        JOIN lieux l ON l.id = s.lieu_id';

    public function trouverParId(int $id): ?Soiree
    {
        // la soiree et son lieu
        $requete = $this->pdo->prepare(self::SELECT . ' WHERE s.id = :id');
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

        return $this->construire($l, $this->hydrator->hydrater($spectacles->fetchAll(PDO::FETCH_ASSOC)));
    }

    public function lister(): array
    {
        // deux requetes en tout : les soirees, puis tous les spectacles regroupes par soiree
        $requete = $this->pdo->prepare(self::SELECT . ' ORDER BY s.date_soiree');
        $requete->execute();
        $lignes = $requete->fetchAll(PDO::FETCH_ASSOC);

        $spectacles = $this->pdo->prepare(SpectacleHydrator::SELECT . ' ORDER BY sp.horaire_previsionnel');
        $spectacles->execute();
        $parSoiree = [];
        foreach ($this->hydrator->hydrater($spectacles->fetchAll(PDO::FETCH_ASSOC)) as $spectacle) {
            $parSoiree[$spectacle->soireeId()][] = $spectacle;
        }

        return array_map(fn (array $l): Soiree => $this->construire($l, $parSoiree[(int) $l['id']] ?? []), $lignes);
    }

    public function verrouiller(array $ids): array
    {
        if ($ids === []) {
            return [];
        }
        // toujours dans le meme ordre (par id) : deux paiements simultanes ne se bloquent pas mutuellement
        $ids = array_values(array_unique(array_map('intval', $ids)));
        sort($ids);
        $marqueurs = implode(', ', array_fill(0, count($ids), '?'));

        $requete = $this->pdo->prepare(self::SELECT . " WHERE s.id IN ({$marqueurs}) ORDER BY s.id FOR UPDATE");
        $spectacles = $this->pdo->prepare(
            SpectacleHydrator::SELECT . " WHERE sp.soiree_id IN ({$marqueurs}) ORDER BY sp.horaire_previsionnel"
        );
        foreach ($ids as $i => $id) {
            $requete->bindValue($i + 1, $id, PDO::PARAM_INT);
            $spectacles->bindValue($i + 1, $id, PDO::PARAM_INT);
        }
        $requete->execute();
        $lignes = $requete->fetchAll(PDO::FETCH_ASSOC);
        $spectacles->execute();

        $parSoiree = [];
        foreach ($this->hydrator->hydrater($spectacles->fetchAll(PDO::FETCH_ASSOC)) as $spectacle) {
            $parSoiree[$spectacle->soireeId()][] = $spectacle;
        }

        $soirees = [];
        foreach ($lignes as $l) {
            $soirees[(int) $l['id']] = $this->construire($l, $parSoiree[(int) $l['id']] ?? []);
        }

        return $soirees;
    }

    public function enregistrerPlacesVendues(Soiree $soiree): void
    {
        $requete = $this->pdo->prepare('UPDATE soirees SET places_vendues = :vendues WHERE id = :id');
        $requete->bindValue('vendues', $soiree->placesVendues(), PDO::PARAM_INT);
        $requete->bindValue('id', $soiree->id(), PDO::PARAM_INT);
        $requete->execute();
    }

    private function construire(array $l, array $spectacles): Soiree
    {
        return new Soiree(
            (int) $l['id'],
            (string) $l['nom'],
            (string) $l['thematique'],
            new DateTimeImmutable($l['date_soiree'] . ' ' . $l['heure_debut']),
            new Lieu((int) $l['lieu_id'], (string) $l['lieu_nom'], (string) $l['lieu_adresse'], (int) $l['lieu_capacite']),
            new Tarifs((int) $l['tarif_normal'], (int) $l['tarif_reduit']),
            (int) $l['jauge'],
            (int) $l['places_vendues'],
            $spectacles,
        );
    }
}
