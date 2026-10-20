<?php

declare(strict_types=1);

namespace Nrv\Domain\Exception;

final class SpectacleIntrouvableException extends NrvException
{
    public static function avecId(int $id): self
    {
        return new self("spectacle {$id} introuvable");
    }
}
