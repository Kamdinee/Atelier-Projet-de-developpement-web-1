<?php

declare(strict_types=1);

namespace Nrv\Infrastructure\Persistence\Pdo;

use DateTimeImmutable;
use Nrv\Domain\Entity\Spectacle;
use Nrv\Domain\Entity\Style;
use Nrv\Domain\Repository\SpectacleRepositoryInterface;
use Nrv\Domain\ValueObject\CriteresSpectacles;
use Nrv\Domain\ValueObject\ResultatPagine;
use PDO;

// adaptateur pdo du port SpectacleRepositoryInterface
final class PDOSpectacleRepository implements SpectacleRepositoryInterface
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly SpectacleHydrator $hydrator,
    ) {
    }

    public function rechercher(CriteresSpectacles $criteres): ResultatPagine
    {
        // on n'ajoute que les filtres renseignes
        $conditions = [];
        $parametres = [];
        if ($criteres->date() !== null) {
            $conditions[] = 'so.date_soiree = :date';
            $parametres['date'] = $criteres->date()->format('Y-m-d');
        }
        if ($criteres->style() !== null) {
            $conditions[] = 'st.slug = :style';
            $parametres['style'] = $criteres->style();
        }
        $where = $conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions);

        // nombre total pour la pagination
        $compte = $this->pdo->prepare(
            'SELECT COUNT(*) FROM spectacles sp
            JOIN soirees so ON so.id = sp.soiree_id
            JOIN styles st ON st.id = sp.style_id' . $where
        );
        $compte->execute($parametres);
        $total = (int) $compte->fetchColumn();

        // la page demandee, dans l'ordre du programme
        $requete = $this->pdo->prepare(
            SpectacleHydrator::SELECT . $where
            . ' ORDER BY so.date_soiree, sp.horaire_previsionnel, sp.id LIMIT :limite OFFSET :decalage'
        );
        foreach ($parametres as $nom => $valeur) {
            $requete->bindValue($nom, $valeur, PDO::PARAM_STR);
        }
        $requete->bindValue('limite', $criteres->taille(), PDO::PARAM_INT);
        $requete->bindValue('decalage', $criteres->decalage(), PDO::PARAM_INT);
        $requete->execute();

        return new ResultatPagine(
            $this->hydrator->hydrater($requete->fetchAll(PDO::FETCH_ASSOC)),
            $total,
            $criteres->page(),
            $criteres->taille(),
        );
    }

    public function trouverParId(int $id): ?Spectacle
    {
        $requete = $this->pdo->prepare(SpectacleHydrator::SELECT . ' WHERE sp.id = :id');
        $requete->bindValue('id', $id, PDO::PARAM_INT);
        $requete->execute();

        $ligne = $requete->fetch(PDO::FETCH_ASSOC);
        if ($ligne === false) {
            return null;
        }

        return $this->hydrator->hydrater([$ligne])[0];
    }

    public function stylesDisponibles(): array
    {
        $requete = $this->pdo->prepare(
            'SELECT DISTINCT st.id, st.libelle, st.slug
            FROM styles st
            JOIN spectacles sp ON sp.style_id = st.id
            ORDER BY st.libelle'
        );
        $requete->execute();

        return array_map(
            static fn (array $l): Style => new Style((int) $l['id'], (string) $l['libelle'], (string) $l['slug']),
            $requete->fetchAll(PDO::FETCH_ASSOC),
        );
    }

    public function datesDisponibles(): array
    {
        $requete = $this->pdo->prepare('SELECT date_soiree FROM soirees ORDER BY date_soiree');
        $requete->execute();

        return array_map(
            static fn (string $date): DateTimeImmutable => new DateTimeImmutable($date),
            $requete->fetchAll(PDO::FETCH_COLUMN),
        );
    }
}
