<?php

declare(strict_types=1);

namespace Nrv\Domain\Exception;

// une seule soiree par date pendant le festival
final class DateDejaPriseException extends NrvException
{
    public static function pour(string $date): self
    {
        return new self("une soiree est deja programmee le {$date}");
    }
}
