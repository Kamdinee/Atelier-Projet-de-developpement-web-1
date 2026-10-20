<?php

declare(strict_types=1);

namespace Nrv\Application\Port\In;

use Nrv\Application\Dto\StyleDto;

// cas d'usage : remplir les listes de filtres (styles et dates)
interface ListerFiltresProgrammeInterface
{
    /**
     * @return list<StyleDto>
     */
    public function styles(): array;

    /**
     * @return list<string> dates au format Y-m-d
     */
    public function dates(): array;
}
