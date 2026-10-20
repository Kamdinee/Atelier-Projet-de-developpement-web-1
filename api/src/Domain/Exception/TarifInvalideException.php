<?php

declare(strict_types=1);

namespace Nrv\Domain\Exception;

// tarif negatif ou reduit plus cher que le normal
final class TarifInvalideException extends DonneeInvalideException
{
}
