<?php

declare(strict_types=1);

namespace Nrv\Domain\ValueObject;

enum CategorieTarif: string
{
    case Normal = 'NORMAL';
    case Reduit = 'REDUIT';
}
