<?php

declare(strict_types=1);

namespace Nrv\Application\UseCase;

use Nrv\Application\Dto\BilletDto;
use Nrv\Application\Port\In\ConsulterBilletInterface;
use Nrv\Application\Port\Out\BilletRepositoryInterface;
use Nrv\Domain\Exception\BilletIntrouvableException;
use Nrv\Domain\ValueObject\JetonBillet;

final class ConsulterBillet implements ConsulterBilletInterface
{
    public function __construct(private readonly BilletRepositoryInterface $billets)
    {
    }

    public function executer(string $jeton): BilletDto
    {
        // un jeton mal forme ne peut correspondre a aucun billet
        $billet = JetonBillet::estValide($jeton) ? $this->billets->trouverParJeton(strtolower($jeton)) : null;
        if ($billet === null) {
            throw BilletIntrouvableException::pour('demande');
        }

        return BilletDto::depuis($billet);
    }
}
