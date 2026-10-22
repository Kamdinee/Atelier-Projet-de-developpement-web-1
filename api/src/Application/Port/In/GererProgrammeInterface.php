<?php

declare(strict_types=1);

namespace Nrv\Application\Port\In;

use Nrv\Application\Dto\NouveauSpectacleDto;
use Nrv\Application\Dto\NouvelleSoireeDto;
use Nrv\Application\Dto\ReferentielsDto;

// cas d'usage : backoffice, ajout de soirees et de spectacles (E3)
interface GererProgrammeInterface
{
    // lieux et styles proposes dans les formulaires
    public function referentiels(): ReferentielsDto;

    // cree une soiree avec ses 2 a 3 spectacles, renvoie son id
    public function ajouterSoiree(NouvelleSoireeDto $soiree): int;

    // ajoute un spectacle a une soiree qui en a moins de 3, renvoie son id
    public function ajouterSpectacle(int $soireeId, NouveauSpectacleDto $spectacle): int;
}
