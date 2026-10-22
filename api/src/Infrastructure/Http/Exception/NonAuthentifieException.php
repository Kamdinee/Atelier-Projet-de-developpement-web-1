<?php

declare(strict_types=1);

namespace Nrv\Infrastructure\Http\Exception;

// jeton absent, invalide ou expire : reponse 401
final class NonAuthentifieException extends \RuntimeException
{
}
