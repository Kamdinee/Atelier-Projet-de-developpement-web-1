<?php

declare(strict_types=1);

namespace Nrv\Application\UseCase;

use Nrv\Application\Dto\JaugeSoireeDto;
use Nrv\Application\Dto\JaugesDto;
use Nrv\Application\Port\In\ConsulterJaugesInterface;
use Nrv\Application\Port\Out\JaugeRepositoryInterface;
use Nrv\Domain\ValueObject\JaugeSoiree;

final class ConsulterJauges implements ConsulterJaugesInterface
{
    public function __construct(private readonly JaugeRepositoryInterface $jauges)
    {
    }

    public function executer(): JaugesDto
    {
        $soirees = $this->jauges->toutes();

        $jauge = 0;
        $vendues = 0;
        $recette = 0;
        $completes = 0;
        $presque = 0;
        foreach ($soirees as $s) {
            $jauge += $s->jauge();
            $vendues += $s->placesVendues();
            $recette += $s->recette();
            $completes += $s->estComplete() ? 1 : 0;
            $presque += $s->estPresqueComplete() ? 1 : 0;
        }

        return new JaugesDto(
            $jauge,
            $vendues,
            $jauge > 0 ? round(100 * $vendues / $jauge, 1) : 0.0,
            $recette,
            $completes,
            $presque,
            array_map(static fn (JaugeSoiree $s): JaugeSoireeDto => JaugeSoireeDto::depuis($s), $soirees),
        );
    }
}
