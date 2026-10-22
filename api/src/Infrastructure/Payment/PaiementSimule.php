<?php

declare(strict_types=1);

namespace Nrv\Infrastructure\Payment;

use Nrv\Application\Port\Out\PasserelleBancaireInterface;
use Nrv\Domain\Exception\PaiementRefuseException;
use Nrv\Domain\ValueObject\CarteBancaire;

// adaptateur du port PasserelleBancaireInterface : banque simulee (aucun appel exterieur)
// toute carte valide est acceptee, sauf le numero de test ci-dessous qui est toujours refuse
final class PaiementSimule implements PasserelleBancaireInterface
{
    public const NUMERO_REFUSE = '4000000000000002';

    public function debiter(CarteBancaire $carte, int $montantCentimes): void
    {
        if ($carte->numero() === self::NUMERO_REFUSE) {
            throw new PaiementRefuseException();
        }
    }
}
