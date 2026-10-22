<?php

declare(strict_types=1);

namespace Nrv\Application\Port\Out;

interface PassRepositoryInterface
{
    /**
     * @return list<array{
     *     id: int,
     *     libelle: string,
     *     nb_soirees: int,
     *     tarif_normal: int,
     *     tarif_reduit: int
     * }>
     */
    public function lister(): array;

    /**
     * @return array{
     *     id: int,
     *     libelle: string,
     *     nb_soirees: int,
     *     tarif_normal: int,
     *     tarif_reduit: int
     * }|null
     */
    public function trouverParId(int $id): ?array;
}
