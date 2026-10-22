<?php

declare(strict_types=1);

namespace Nrv\Application\Port\Out;

use Nrv\Domain\Entity\Soiree;

// port de sortie (output port) : acces aux soirees, implemente par un adaptateur de persistance
interface SoireeRepositoryInterface
{
    // soiree complete avec son lieu et ses 2 a 3 spectacles, null si inconnue
    public function trouverParId(int $id): ?Soiree;

    // toutes les soirees, dans l'ordre des dates
    /** @return list<Soiree> */
    public function lister(): array;

    // soirees lues et verrouillees jusqu'a la fin de la transaction (pas de survente)
    /**
     * @param list<int> $ids
     * @return array<int, Soiree> id => soiree
     */
    public function verrouiller(array $ids): array;

    public function enregistrerPlacesVendues(Soiree $soiree): void;
}
