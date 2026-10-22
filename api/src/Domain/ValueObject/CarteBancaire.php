<?php

declare(strict_types=1);

namespace Nrv\Domain\ValueObject;

use DateTimeImmutable;
use Nrv\Domain\Exception\DonneeInvalideException;

// carte du paiement simule : controlee puis oubliee, seuls les 4 derniers chiffres sont gardes
final class CarteBancaire
{
    private readonly string $numero;

    public function __construct(
        string $numero,
        private readonly int $moisExpiration,
        private readonly int $anneeExpiration,
        string $cryptogramme,
    ) {
        $numero = preg_replace('/[\s-]/', '', $numero) ?? '';
        if (preg_match('/^\d{13,19}$/', $numero) !== 1 || !self::luhn($numero)) {
            throw new DonneeInvalideException('numero de carte invalide');
        }
        if ($moisExpiration < 1 || $moisExpiration > 12 || $anneeExpiration < 2000 || $anneeExpiration > 2099) {
            throw new DonneeInvalideException("date d'expiration invalide");
        }
        if (preg_match('/^\d{3,4}$/', $cryptogramme) !== 1) {
            throw new DonneeInvalideException('cryptogramme invalide');
        }
        $this->numero = $numero;
    }

    // algorithme de luhn : detecte les fautes de frappe dans un numero de carte
    private static function luhn(string $numero): bool
    {
        $somme = 0;
        foreach (array_reverse(str_split($numero)) as $i => $chiffre) {
            $n = (int) $chiffre * ($i % 2 === 1 ? 2 : 1);
            $somme += $n > 9 ? $n - 9 : $n;
        }

        return $somme % 10 === 0;
    }

    // la carte reste valable jusqu'a la fin de son mois d'expiration
    public function estExpiree(DateTimeImmutable $maintenant): bool
    {
        return (int) $maintenant->format('Y') * 12 + (int) $maintenant->format('n')
            > $this->anneeExpiration * 12 + $this->moisExpiration;
    }

    // numero complet : n'est transmis qu'a la passerelle bancaire, jamais enregistre
    public function numero(): string
    {
        return $this->numero;
    }

    public function fin(): string
    {
        return substr($this->numero, -4);
    }
}
