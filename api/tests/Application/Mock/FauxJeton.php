<?php

declare(strict_types=1);

namespace Nrv\Tests\Application\Mock;

use Nrv\Application\Port\Out\JetonInterface;
use Nrv\Domain\Entity\Utilisateur;

final class FauxJeton implements JetonInterface
{
    public function creer(Utilisateur $utilisateur): string
    {
        return 'jeton-' . $utilisateur->id() . '-' . $utilisateur->role()->value;
    }

    public function expiration(): string
    {
        return '2027-06-04T21:00:00+00:00';
    }
}
