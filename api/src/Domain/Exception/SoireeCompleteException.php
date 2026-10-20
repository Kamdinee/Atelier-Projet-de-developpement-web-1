<?php

declare(strict_types=1);

namespace Nrv\Domain\Exception;

// la soiree n'a plus aucune place
final class SoireeCompleteException extends PlacesInsuffisantesException
{
}
