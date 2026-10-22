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

    public function lister(): array
    {
        $requete = $this->pdo->prepare('SELECT id, libelle, nb_soirees, tarif_normal, tarif_reduit FROM pass ORDER BY id');
        $requete->execute();

        return array_map(
            static fn (array $l): Pass => new Pass(
                (int) $l['id'],
                (string) $l['libelle'],
                (int) $l['nb_soirees'],
                new Tarifs((int) $l['tarif_normal'], (int) $l['tarif_reduit']),
            ),
            $requete->fetchAll(PDO::FETCH_ASSOC),
        );
    }
}
