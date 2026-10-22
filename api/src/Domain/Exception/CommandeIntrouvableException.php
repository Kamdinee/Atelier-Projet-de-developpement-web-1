<?php

declare(strict_types=1);

namespace Nrv\Domain\Exception;

final class CommandeIntrouvableException extends NrvException
{
    public static function pour(string $cle): self
    {
        return new self(strtolower('Commande') . " {$cle} introuvable");
    }
}
