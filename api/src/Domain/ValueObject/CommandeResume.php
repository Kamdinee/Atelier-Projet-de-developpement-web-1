<?php

declare(strict_types=1);

namespace Nrv\Domain\ValueObject;

use DateTimeImmutable;
use Nrv\Domain\Exception\DonneeInvalideException;

// une commande vue depuis l'espace client : statut, montant, nombre de billets
final class CommandeResume
{
    public const STATUTS = ['EN_ATTENTE', 'PAYEE', 'ANNULEE'];

    public function __construct(
        private readonly string $id,
        private readonly string $statut,
        private readonly int $total,
        private readonly int $nombreBillets,
        private readonly DateTimeImmutable $creeLe,
        private readonly ?DateTimeImmutable $payeeLe,
    ) {
        if (!in_array($statut, self::STATUTS, true)) {
            throw new DonneeInvalideException("statut de commande inconnu : {$statut}");
        }
        if ($total < 0 || $nombreBillets < 0) {
            throw new DonneeInvalideException('montant et nombre de billets ne peuvent pas etre negatifs');
        }
    }

    public function id(): string
    {
        return $this->id;
    }

    public function statut(): string
    {
        return $this->statut;
    }

    public function estPayee(): bool
    {
        return $this->statut === 'PAYEE';
    }

    public function total(): int
    {
        return $this->total;
    }

    public function nombreBillets(): int
    {
        return $this->nombreBillets;
    }

    public function creeLe(): DateTimeImmutable
    {
        return $this->creeLe;
    }

    public function payeeLe(): ?DateTimeImmutable
    {
        return $this->payeeLe;
    }
}
