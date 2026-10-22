<?php

declare(strict_types=1);

namespace Nrv\Infrastructure\Persistence\Pdo;

use Nrv\Application\Port\Out\PassRepositoryInterface;
use Nrv\Domain\Entity\Pass;
use Nrv\Domain\ValueObject\Tarifs;
use PDO;

// adaptateur pdo du port PassRepositoryInterface
final class PDOPassRepository implements PassRepositoryInterface
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    private const SELECT = 'SELECT id, libelle, nb_soirees, tarif_normal, tarif_reduit FROM pass';

    public function lister(): array
    {
        $requete = $this->pdo->prepare(self::SELECT . ' ORDER BY id');
        $requete->execute();

        return array_map(fn (array $l): Pass => $this->hydrater($l), $requete->fetchAll(PDO::FETCH_ASSOC));
    }

    public function trouverParId(int $id): ?Pass
    {
        $requete = $this->pdo->prepare(self::SELECT . ' WHERE id = :id');
        $requete->bindValue('id', $id, PDO::PARAM_INT);
        $requete->execute();
        $ligne = $requete->fetch(PDO::FETCH_ASSOC);

        return $ligne === false ? null : $this->hydrater($ligne);
    }

    private function hydrater(array $l): Pass
    {
        return new Pass(
            (int) $l['id'],
            (string) $l['libelle'],
            (int) $l['nb_soirees'],
            new Tarifs((int) $l['tarif_normal'], (int) $l['tarif_reduit']),
        );
    }
}
