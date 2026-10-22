<?php

declare(strict_types=1);

namespace Nrv\Application\UseCase;

use Nrv\Application\Dto\PassDto;
use Nrv\Application\Port\In\ConsulterPassInterface;
use Nrv\Application\Port\Out\PassRepositoryInterface;
use Nrv\Domain\Entity\Pass;

final class ConsulterPass implements ConsulterPassInterface
{
    public function __construct(private readonly PassRepositoryInterface $pass)
    {
    }

    public function executer(): array
    {
        return array_map(static fn (Pass $p): PassDto => PassDto::depuis($p), $this->pass->lister());
    }
}
