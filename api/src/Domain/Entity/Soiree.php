<?php

declare(strict_types=1);

namespace Nrv\Domain\Entity;

use DateTimeImmutable;
use Nrv\Domain\Exception\DonneeInvalideException;
use Nrv\Domain\Exception\NombreSpectaclesInvalideException;
use Nrv\Domain\Exception\PlacesInsuffisantesException;
use Nrv\Domain\Exception\QuantiteInvalideException;
use Nrv\Domain\Exception\SoireeCompleteException;
use Nrv\Domain\ValueObject\CategorieTarif;
use Nrv\Domain\ValueObject\Tarifs;

// une soiree du festival : date, lieu, tarifs, jauge et 2 a 3 spectacles
final class Soiree
{
    public const MIN_SPECTACLES = 2;
    public const MAX_SPECTACLES = 3;

    /** @var list<Spectacle> */
    private readonly array $spectacles;

    private int $placesVendues;

    /**
     * @param list<Spectacle> $spectacles
     */
    public function __construct(
        private readonly int $id,
        private readonly string $nom,
        private readonly string $thematique,
        private readonly DateTimeImmutable $debut,
        private readonly Lieu $lieu,
        private readonly Tarifs $tarifs,
        private readonly int $jauge,
        int $placesVendues,
        array $spectacles,
    ) {
        if ($jauge < 1) {
            throw new DonneeInvalideException('la jauge doit etre positive');
        }
        if ($jauge > $lieu->capacite()) {
            throw new DonneeInvalideException('la jauge ne peut pas depasser la capacite du lieu');
        }
        if ($placesVendues < 0 || $placesVendues > $jauge) {
            throw new DonneeInvalideException('le nombre de places vendues doit etre entre 0 et la jauge');
        }

        $nombre = count($spectacles);
        if ($nombre < self::MIN_SPECTACLES || $nombre > self::MAX_SPECTACLES) {
            throw NombreSpectaclesInvalideException::pour($nombre);
        }
        foreach ($spectacles as $spectacle) {
            if (!$spectacle instanceof Spectacle || $spectacle->soireeId() !== $id) {
                throw new DonneeInvalideException("un spectacle n'appartient pas a la soiree {$id}");
            }
        }

        // spectacles dans l'ordre de passage
        usort($spectacles, static fn (Spectacle $a, Spectacle $b) => $a->horaire() <=> $b->horaire());

        $this->spectacles = $spectacles;
        $this->placesVendues = $placesVendues;
    }

    public function id(): int
    {
        return $this->id;
    }

    public function nom(): string
    {
        return $this->nom;
    }

    public function thematique(): string
    {
        return $this->thematique;
    }

    // date et heure de debut
    public function debut(): DateTimeImmutable
    {
        return $this->debut;
    }

    public function lieu(): Lieu
    {
        return $this->lieu;
    }

    public function tarifs(): Tarifs
    {
        return $this->tarifs;
    }

    public function prix(CategorieTarif $categorie): int
    {
        return $this->tarifs->pour($categorie);
    }

    public function jauge(): int
    {
        return $this->jauge;
    }

    public function placesVendues(): int
    {
        return $this->placesVendues;
    }

    public function placesRestantes(): int
    {
        return $this->jauge - $this->placesVendues;
    }

    public function estComplete(): bool
    {
        return $this->placesRestantes() === 0;
    }

    public function peutAccueillir(int $nombre): bool
    {
        return $nombre >= 1 && $nombre <= $this->placesRestantes();
    }

    // prend des places dans la jauge, sinon exception
    public function reserver(int $nombre): void
    {
        if ($nombre < 1) {
            throw new QuantiteInvalideException('il faut reserver au moins une place');
        }
        if ($this->estComplete()) {
            throw new SoireeCompleteException($this->id, $nombre, 0);
        }
        if ($nombre > $this->placesRestantes()) {
            throw new PlacesInsuffisantesException($this->id, $nombre, $this->placesRestantes());
        }

        $this->placesVendues += $nombre;
    }

    /**
     * @return list<Spectacle>
     */
    public function spectacles(): array
    {
        return $this->spectacles;
    }
}
