<?php

declare(strict_types=1);

namespace Nrv\Domain\Exception;

// une soiree doit avoir 2 ou 3 spectacles
final class NombreSpectaclesInvalideException extends DonneeInvalideException
{
    public static function pour(int $nombre): self
    {
        return new self("une soiree doit avoir entre 2 et 3 spectacles, {$nombre} donnes");
    }
}
