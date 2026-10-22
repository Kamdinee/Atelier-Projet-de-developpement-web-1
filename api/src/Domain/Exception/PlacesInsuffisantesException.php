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
        $msg = $restantes <= 0
            ? "La soirée n°{$soireeId} est désormais complète (0 place restante)."
            : "Il ne reste que {$restantes} place(s) pour la soirée n°{$soireeId} ({$demandees} demandée(s)).";
        parent::__construct($msg);
    }
}
