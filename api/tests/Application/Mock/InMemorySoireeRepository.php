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

    /** @var list<int> soirees enregistrees (pour verifier qu'un refus n'ecrit rien) */
    public array $enregistrees = [];

    public function verrouiller(array $ids): array
    {
        return array_intersect_key($this->soirees, array_flip($ids));
    }

    public function enregistrerPlacesVendues(Soiree $soiree): void
    {
        $this->enregistrees[] = $soiree->id();
    }

    public function lister(): array
    {
        $soirees = array_values($this->soirees);
        usort($soirees, static fn (Soiree $a, Soiree $b) => $a->debut() <=> $b->debut());

        return $soirees;
    }
}
