<?php

declare(strict_types=1);

namespace Nrv\Domain\ValueObject;

// une page de resultats + le nombre total d'elements
final class ResultatPagine
{
    /**
     * @param list<object> $elements
     */
    public function __construct(
        private readonly array $elements,
        private readonly int $total,
        private readonly int $page,
        private readonly int $taille,
    ) {
    }

    /**
     * @return list<object>
     */
    public function elements(): array
    {
        return $this->elements;
    }

    public function total(): int
    {
        return $this->total;
    }

    public function page(): int
    {
        return $this->page;
    }

    public function taille(): int
    {
        return $this->taille;
    }

    public function nbPages(): int
    {
        return max(1, (int) ceil($this->total / $this->taille));
    }
}
