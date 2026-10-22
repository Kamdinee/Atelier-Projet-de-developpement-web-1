<?php

declare(strict_types=1);

namespace Nrv\Domain\ValueObject;

use DateTimeImmutable;
use Nrv\Domain\Exception\DonneeInvalideException;

// ce qu'un billet imprime d'une soiree : nom, date, lieu et horaires des spectacles
final class SoireeBillet
{
    /** @var list<array{titre: string, horaire: string}> */
    private readonly array $spectacles;

    /**
     * @param list<array{titre: string, horaire: string}> $spectacles
     */
    public function __construct(
        private readonly int $id,
        private readonly string $nom,
        private readonly DateTimeImmutable $debut,
        private readonly string $lieu,
        private readonly string $adresse,
        array $spectacles,
    ) {
        if ($id < 1 || trim($nom) === '') {
            throw new DonneeInvalideException('soiree de billet invalide');
        }
        $this->spectacles = array_values($spectacles);
    }

    public function id(): int
    {
        return $this->id;
    }

    public function nom(): string
    {
        return $this->nom;
    }

    public function debut(): DateTimeImmutable
    {
        return $this->debut;
    }

    public function lieu(): string
    {
        return $this->lieu;
    }

    public function adresse(): string
    {
        return $this->adresse;
    }

    /**
     * @return list<array{titre: string, horaire: string}>
     */
    public function spectacles(): array
    {
        return $this->spectacles;
    }
}
