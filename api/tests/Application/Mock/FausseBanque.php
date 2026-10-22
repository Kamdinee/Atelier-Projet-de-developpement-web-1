<?php

declare(strict_types=1);

namespace Nrv\Tests\Application\Mock;

use Nrv\Application\Port\Out\PasserelleBancaireInterface;
use Nrv\Domain\Exception\PaiementRefuseException;
use Nrv\Domain\ValueObject\CarteBancaire;

// banque : accepte tout, ou refuse tout ; garde les debits
final class FausseBanque implements PasserelleBancaireInterface
{
    /** @var list<int> */
    public array $debits = [];

    public function __construct(private readonly bool $refuser = false)
    {
    }

    public function debiter(CarteBancaire $carte, int $montantCentimes): void
    {
        if ($this->refuser) {
            throw new PaiementRefuseException();
        }
        $this->debits[] = $montantCentimes;
    }
}
