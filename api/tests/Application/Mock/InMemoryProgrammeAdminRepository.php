<?php

declare(strict_types=1);

namespace Nrv\Tests\Application\Mock;

use DateTimeImmutable;
use Nrv\Application\Port\Out\ProgrammeAdminRepositoryInterface;
use Nrv\Domain\Entity\Lieu;
use Nrv\Domain\Entity\Soiree;
use Nrv\Domain\Entity\Spectacle;
use Nrv\Domain\Entity\Style;

// faux depot du backoffice : garde ce qui a ete enregistre pour les assertions
final class InMemoryProgrammeAdminRepository implements ProgrammeAdminRepositoryInterface
{
    /** @var list<Soiree> */
    public array $soireesAjoutees = [];

    /** @var list<Spectacle> */
    public array $spectaclesAjoutes = [];

    /**
     * @param list<Lieu> $lieux
     * @param list<Style> $styles
     * @param list<string> $datesOccupees AAAA-MM-JJ
     */
    public function __construct(
        private readonly array $lieux,
        private readonly array $styles,
        private readonly array $datesOccupees = [],
    ) {
    }

    public function lieu(int $id): ?Lieu
    {
        foreach ($this->lieux as $lieu) {
            if ($lieu->id() === $id) {
                return $lieu;
            }
        }

        return null;
    }

    public function lieux(): array
    {
        return $this->lieux;
    }

    public function styleParSlug(string $slug): ?Style
    {
        foreach ($this->styles as $style) {
            if ($style->slug() === $slug) {
                return $style;
            }
        }

        return null;
    }

    public function styles(): array
    {
        return $this->styles;
    }

    public function dateOccupee(DateTimeImmutable $date): bool
    {
        return in_array($date->format('Y-m-d'), $this->datesOccupees, true);
    }

    public function ajouterSoiree(Soiree $soiree): int
    {
        $this->soireesAjoutees[] = $soiree;

        return 100 + count($this->soireesAjoutees);
    }

    public function ajouterSpectacle(Spectacle $spectacle): int
    {
        $this->spectaclesAjoutes[] = $spectacle;

        return 500 + count($this->spectaclesAjoutes);
    }
}
