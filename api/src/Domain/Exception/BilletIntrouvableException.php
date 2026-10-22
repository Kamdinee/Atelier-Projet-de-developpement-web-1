<?php

declare(strict_types=1);

namespace Nrv\Domain\Exception;

final class BilletIntrouvableException extends NrvException
{
    public static function pour(string $cle): self
    {
        return new self(strtolower('Billet') . " {$cle} introuvable");
    }
}
