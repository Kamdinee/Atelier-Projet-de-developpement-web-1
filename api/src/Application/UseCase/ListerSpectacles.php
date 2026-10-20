<?php

declare(strict_types=1);

namespace Nrv\Application\UseCase;

use DateTimeImmutable;
use Nrv\Application\Dto\FiltreSpectaclesDto;
use Nrv\Application\Dto\PageSpectaclesDto;
use Nrv\Application\Dto\SpectacleDto;
use Nrv\Application\Port\In\ListerSpectaclesInterface;
use Nrv\Application\Port\Out\SpectacleRepositoryInterface;
use Nrv\Domain\Entity\Spectacle;
use Nrv\Domain\Exception\DonneeInvalideException;
use Nrv\Domain\ValueObject\CriteresSpectacles;

final class ListerSpectacles implements ListerSpectaclesInterface
{
    public function __construct(private readonly SpectacleRepositoryInterface $spectacles)
    {
    }

    public function executer(FiltreSpectaclesDto $filtre): PageSpectaclesDto
    {
        $date = null;
        if ($filtre->date !== null) {
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $filtre->date);
            if ($date === false) {
                throw new DonneeInvalideException("date invalide : {$filtre->date}");
            }
        }

        $resultat = $this->spectacles->rechercher(
            new CriteresSpectacles($date, $filtre->style, $filtre->page, $filtre->taille)
        );

        /** @var list<Spectacle> $elements */
        $elements = $resultat->elements();

        return new PageSpectaclesDto(
            array_map(static fn (Spectacle $s): SpectacleDto => SpectacleDto::depuis($s), $elements),
            $resultat->total(),
            $resultat->page(),
            $resultat->taille(),
            $resultat->nbPages(),
            $filtre,
        );
    }
}
