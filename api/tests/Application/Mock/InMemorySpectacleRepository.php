<?php

declare(strict_types=1);

namespace Nrv\Tests\Application\Mock;

use DateTimeImmutable;
use Nrv\Application\Port\Out\SpectacleRepositoryInterface;
use Nrv\Domain\Entity\Spectacle;
use Nrv\Domain\ValueObject\CriteresSpectacles;
use Nrv\Domain\ValueObject\ResultatPagine;

// faux depot en memoire : branche sur le port de sortie a la place de pdo
final class InMemorySpectacleRepository implements SpectacleRepositoryInterface
{
    /**
     * @param list<Spectacle> $spectacles
     */
    public function __construct(private readonly array $spectacles)
    {
    }

    public function rechercher(CriteresSpectacles $criteres): ResultatPagine
    {
        $trouves = array_values(array_filter(
            $this->spectacles,
            static function (Spectacle $s) use ($criteres): bool {
                $bonneDate = $criteres->date() === null
                    || $s->horaire()->format('Y-m-d') === $criteres->date()->format('Y-m-d');
                $bonStyle = $criteres->style() === null || $s->style()->slug() === $criteres->style();

                return $bonneDate && $bonStyle;
            },
        ));
        usort($trouves, static fn (Spectacle $a, Spectacle $b) => $a->horaire() <=> $b->horaire());

        return new ResultatPagine(
            array_slice($trouves, $criteres->decalage(), $criteres->taille()),
            count($trouves),
            $criteres->page(),
            $criteres->taille(),
        );
    }

    public function trouverParId(int $id): ?Spectacle
    {
        foreach ($this->spectacles as $spectacle) {
            if ($spectacle->id() === $id) {
                return $spectacle;
            }
        }

        return null;
    }

    public function stylesDisponibles(): array
    {
        $styles = [];
        foreach ($this->spectacles as $spectacle) {
            $styles[$spectacle->style()->slug()] = $spectacle->style();
        }
        ksort($styles);

        return array_values($styles);
    }

    public function datesDisponibles(): array
    {
        $dates = [];
        foreach ($this->spectacles as $spectacle) {
            $jour = $spectacle->horaire()->format('Y-m-d');
            $dates[$jour] = new DateTimeImmutable($jour);
        }
        ksort($dates);

        return array_values($dates);
    }
}
