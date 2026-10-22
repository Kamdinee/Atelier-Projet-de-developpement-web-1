<?php

declare(strict_types=1);

namespace Nrv\Infrastructure\Http\Exception;

// connecte mais sans le bon role : reponse 403
final class AccesInterditException extends \RuntimeException
{
}
