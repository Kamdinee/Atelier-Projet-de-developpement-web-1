<?php

declare(strict_types=1);

namespace Nrv\Application\Port\Out;

use DateTimeImmutable;
use Nrv\Domain\Entity\Lieu;
use Nrv\Domain\Entity\Soiree;
use Nrv\Domain\Entity\Spectacle;
use Nrv\Domain\Entity\Style;

// port de sortie : ajout de soirees et de spectacles depuis le backoffice
interface ProgrammeAdminRepositoryInterface
{
    public function lieu(int $id): ?Lieu;

    /**
     * @return list<Lieu>
     */
    public function lieux(): array;

    public function styleParSlug(string $slug): ?Style;

    /**
     * tous les styles, meme sans spectacle
     *
     * @return list<Style>
     */
    public function styles(): array;

    public function dateOccupee(DateTimeImmutable $date): bool;

    // enregistre la soiree et ses spectacles dans une transaction, renvoie l'id de la soiree
    public function ajouterSoiree(Soiree $soiree): int;

    // enregistre un spectacle dans une soiree existante, renvoie son id
    public function ajouterSpectacle(Spectacle $spectacle): int;
}
