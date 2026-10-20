<?php

declare(strict_types=1);

namespace Nrv\Tests\Application\Mock;

use Nrv\Application\Port\Out\SoireeRepositoryInterface;
use Nrv\Domain\Entity\Soiree;

// faux depot en memoire : branche sur le port de sortie a la place de pdo
final class InMemorySoireeRepository implements SoireeRepositoryInterface
{
    /** @var array<int, Soiree> */
    private array $soirees = [];

    /**
     * @param list<Soiree> $soirees
     */
    public function __construct(array $soirees)
    {
        foreach ($soirees as $soiree) {
            $this->soirees[$soiree->id()] = $soiree;
        }
    }

    public function trouverParId(int $id): ?Soiree
    {
        return $this->soirees[$id] ?? null;
    }
}
