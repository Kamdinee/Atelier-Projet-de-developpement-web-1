<?php

declare(strict_types=1);

namespace Nrv\Domain\Exception;

// email inconnu ou mot de passe faux : meme message dans les deux cas
final class IdentifiantsInvalidesException extends NrvException
{
    public function __construct()
    {
        parent::__construct('email ou mot de passe incorrect');
    }
}
