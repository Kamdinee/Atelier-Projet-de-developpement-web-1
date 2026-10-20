<?php

declare(strict_types=1);

namespace Nrv\Infrastructure\Http\Validation;

use Nrv\Application\Dto\FiltreSpectaclesDto;
use Nrv\Domain\ValueObject\CriteresSpectacles;
use Nrv\Infrastructure\Http\Exception\RequeteInvalideException;

// verifie les parametres de GET /spectacles
final class FiltreSpectaclesValidator
{
    /**
     * @param array<string, mixed> $query
     */
    public function valider(array $query): FiltreSpectaclesDto
    {
        $erreurs = [];

        $date = $this->texte($query, 'date');
        if ($date !== null && !$this->estDate($date)) {
            $erreurs['date'] = 'format attendu : AAAA-MM-JJ';
        }

        $style = $this->texte($query, 'style');
        if ($style !== null && preg_match('/^[a-z0-9-]{1,60}$/', $style) !== 1) {
            $erreurs['style'] = 'style invalide (lettres minuscules, chiffres et tirets)';
        }

        $page = $this->entier($query, 'page', 1);
        if ($page === null || $page < 1) {
            $erreurs['page'] = 'entier superieur ou egal a 1 attendu';
        }

        $taille = $this->entier($query, 'size', CriteresSpectacles::TAILLE_DEFAUT);
        if ($taille === null || $taille < 1 || $taille > CriteresSpectacles::TAILLE_MAX) {
            $erreurs['size'] = 'entier entre 1 et ' . CriteresSpectacles::TAILLE_MAX . ' attendu';
        }

        if ($erreurs !== []) {
            throw new RequeteInvalideException($erreurs);
        }

        return new FiltreSpectaclesDto($date, $style, (int) $page, (int) $taille);
    }

    private function texte(array $query, string $nom): ?string
    {
        if (!isset($query[$nom]) || !is_string($query[$nom]) || trim($query[$nom]) === '') {
            return null;
        }

        return trim($query[$nom]);
    }

    // null si la valeur n'est pas un entier
    private function entier(array $query, string $nom, int $defaut): ?int
    {
        $valeur = $this->texte($query, $nom);
        if ($valeur === null) {
            return $defaut;
        }

        return ctype_digit($valeur) ? (int) $valeur : null;
    }

    private function estDate(string $valeur): bool
    {
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $valeur, $m) !== 1) {
            return false;
        }

        return checkdate((int) $m[2], (int) $m[3], (int) $m[1]);
    }
}
