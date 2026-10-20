<?php

declare(strict_types=1);

namespace Nrv\Domain\ValueObject;

use DateTimeImmutable;
use Nrv\Domain\Exception\DonneeInvalideException;

// filtres et pagination de la liste des spectacles
final class CriteresSpectacles
{
    public const TAILLE_DEFAUT = 12;
    public const TAILLE_MAX = 50;

    public function __construct(
        private readonly ?DateTimeImmutable $date = null,
        private readonly ?string $style = null,
        private readonly int $page = 1,
        private readonly int $taille = self::TAILLE_DEFAUT,
    ) {
        if ($page < 1) {
            throw new DonneeInvalideException('la page doit etre superieure ou egale a 1');
        }
        if ($taille < 1 || $taille > self::TAILLE_MAX) {
            throw new DonneeInvalideException('la taille doit etre comprise entre 1 et ' . self::TAILLE_MAX);
        }
    }

    public function date(): ?DateTimeImmutable
    {
        return $this->date;
    }

    public function style(): ?string
    {
        return $this->style;
    }

    public function page(): int
    {
        return $this->page;
    }

    public function taille(): int
    {
        return $this->taille;
    }

    public function decalage(): int
    {
        return ($this->page - 1) * $this->taille;
    }
}
