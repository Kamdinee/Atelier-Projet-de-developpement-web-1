<?php

declare(strict_types=1);

namespace Nrv\Domain\ValueObject;

// role d'un compte (colonne utilisateurs.role)
enum Role: string
{
    case Client = 'CLIENT';
    case Admin = 'ADMIN';
}
