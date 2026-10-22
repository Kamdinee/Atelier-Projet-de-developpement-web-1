<?php

declare(strict_types=1);

namespace Nrv\Tests\Application\Mock;

use Nrv\Application\Port\Out\PassRepositoryInterface;

final class InMemoryPassRepository implements PassRepositoryInterface
{
    /** @var array<int, array<string, mixed>> */
    private array $pass = [];

    /**
     * @param list<array<string, mixed>> $passList
     */
    public function __construct(array $passList = [])
    {
        foreach ($passList as $p) {
            $this->pass[$p['id']] = $p;
        }
    }

    public function lister(): array
    {
        return array_values($this->pass);
    }

    public function trouverParId(int $id): ?array
    {
        return $this->pass[$id] ?? null;
    }
}
