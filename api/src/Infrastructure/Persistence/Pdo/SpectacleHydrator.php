<?php

declare(strict_types=1);

namespace Nrv\Infrastructure\Persistence\Pdo;

use DateTimeImmutable;
use Nrv\Domain\Entity\Spectacle;
use Nrv\Domain\Entity\Style;
use Nrv\Domain\ValueObject\Image;
use PDO;

// transforme des lignes sql en entites Spectacle
// charge les artistes et les images en 2 requetes pour toute la liste
final class SpectacleHydrator
{
    // colonnes communes a toutes les requetes de spectacles
    public const SELECT = 'SELECT sp.id, sp.soiree_id, sp.titre, sp.description, sp.video_url,
            sp.horaire_previsionnel, so.date_soiree, st.id AS style_id, st.libelle AS style_libelle,
            st.slug AS style_slug
        FROM spectacles sp
        JOIN soirees so ON so.id = sp.soiree_id
        JOIN styles st ON st.id = sp.style_id';

    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * @param list<array<string, mixed>> $lignes
     * @return list<Spectacle>
     */
    public function hydrater(array $lignes): array
    {
        if ($lignes === []) {
            return [];
        }

        $ids = array_map(static fn (array $l): int => (int) $l['id'], $lignes);
        $artistes = $this->artistesPar($ids);
        $images = $this->imagesPar($ids);

        $spectacles = [];
        foreach ($lignes as $l) {
            $id = (int) $l['id'];
            $spectacles[] = new Spectacle(
                $id,
                (int) $l['soiree_id'],
                (string) $l['titre'],
                (string) $l['description'],
                new Style((int) $l['style_id'], (string) $l['style_libelle'], (string) $l['style_slug']),
                $artistes[$id] ?? [],
                $images[$id] ?? [],
                $l['video_url'] !== null ? (string) $l['video_url'] : null,
                new DateTimeImmutable($l['date_soiree'] . ' ' . $l['horaire_previsionnel']),
            );
        }

        return $spectacles;
    }

    /**
     * @param list<int> $ids
     * @return array<int, list<string>>
     */
    private function artistesPar(array $ids): array
    {
        $sql = 'SELECT sa.spectacle_id, a.nom
            FROM spectacle_artiste sa
            JOIN artistes a ON a.id = sa.artiste_id
            WHERE sa.spectacle_id IN (' . $this->marqueurs($ids) . ')
            ORDER BY sa.spectacle_id, sa.ordre';

        $resultat = [];
        foreach ($this->executer($sql, $ids) as $l) {
            $resultat[(int) $l['spectacle_id']][] = (string) $l['nom'];
        }

        return $resultat;
    }

    /**
     * @param list<int> $ids
     * @return array<int, list<Image>>
     */
    private function imagesPar(array $ids): array
    {
        $sql = 'SELECT spectacle_id, url, alt
            FROM spectacle_images
            WHERE spectacle_id IN (' . $this->marqueurs($ids) . ')
            ORDER BY spectacle_id, ordre';

        $resultat = [];
        foreach ($this->executer($sql, $ids) as $l) {
            $resultat[(int) $l['spectacle_id']][] = new Image((string) $l['url'], (string) $l['alt']);
        }

        return $resultat;
    }

    // autant de ? que d'ids, les valeurs restent liees par execute()
    /**
     * @param list<int> $ids
     */
    private function marqueurs(array $ids): string
    {
        return implode(', ', array_fill(0, count($ids), '?'));
    }

    /**
     * @param list<int> $ids
     * @return list<array<string, mixed>>
     */
    private function executer(string $sql, array $ids): array
    {
        $requete = $this->pdo->prepare($sql);
        foreach ($ids as $i => $id) {
            $requete->bindValue($i + 1, $id, PDO::PARAM_INT);
        }
        $requete->execute();

        return $requete->fetchAll(PDO::FETCH_ASSOC);
    }
}
