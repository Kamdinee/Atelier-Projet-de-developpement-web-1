<?php

declare(strict_types=1);

namespace Nrv\Domain\ValueObject;

use Nrv\Domain\Exception\DonneeInvalideException;

// jeton aleatoire (uuid v4) qui donne acces a un billet par son url
final class JetonBillet
{
    private const FORMAT = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/';

    private readonly string $valeur;

    public function __construct(string $valeur)
    {
        $valeur = strtolower($valeur);
        if (!self::estValide($valeur)) {
            throw new DonneeInvalideException('jeton de billet invalide');
        }
        $this->valeur = $valeur;
    }

    public static function estValide(string $valeur): bool
    {
        return preg_match(self::FORMAT, strtolower($valeur)) === 1;
    }

    public function valeur(): string
    {
        return $this->valeur;
    }
}
