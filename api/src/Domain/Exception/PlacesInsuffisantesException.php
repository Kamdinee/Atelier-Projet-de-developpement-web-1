<?php

declare(strict_types=1);

namespace Nrv\Domain\Exception;

// plus assez de places dans la soiree
class PlacesInsuffisantesException extends NrvException
{
    public function __construct(
        public readonly int $soireeId,
        public readonly int $demandees,
        public readonly int $restantes,
    ) {
        parent::__construct("soiree {$soireeId} : {$demandees} places demandees, {$restantes} restantes");
    }
}
