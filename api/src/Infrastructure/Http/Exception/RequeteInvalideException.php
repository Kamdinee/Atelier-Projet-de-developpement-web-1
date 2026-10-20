<?php

declare(strict_types=1);

namespace Nrv\Infrastructure\Http\Exception;

// parametre de requete mal forme : reponse 400
final class RequeteInvalideException extends \RuntimeException
{
    /**
     * @param array<string, string> $erreurs nom du parametre => message
     */
    public function __construct(public readonly array $erreurs)
    {
        parent::__construct('parametres de requete invalides');
    }
}
