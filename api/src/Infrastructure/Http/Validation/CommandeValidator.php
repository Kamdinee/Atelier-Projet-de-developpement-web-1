<?php

declare(strict_types=1);

namespace Nrv\Infrastructure\Http\Validation;

use Nrv\Application\Dto\CarteDto;
use Nrv\Application\Dto\LigneDemandeeDto;
use Nrv\Domain\Entity\Commande;
use Nrv\Domain\ValueObject\LigneCommande;
use Nrv\Infrastructure\Http\Exception\RequeteInvalideException;

// verifie le corps json de POST /commandes et de POST /commandes/{id}/paiement
final class CommandeValidator
{
    // { "lignes": [ { "type": "SOIREE", "soiree_id": 8, "categorie": "NORMAL", "quantite": 2 },
    //               { "type": "PASS", "pass_id": 1, "soirees": [4, 8, 11], "categorie": "REDUIT", "quantite": 1 } ] }
    /** @return list<LigneDemandeeDto> */
    public function commande(mixed $corps): array
    {
        $lignes = is_array($corps) ? ($corps['lignes'] ?? null) : null;
        if (!is_array($lignes) || !array_is_list($lignes) || $lignes === [] || count($lignes) > Commande::LIGNES_MAX) {
            throw new RequeteInvalideException(['lignes' => 'de 1 a ' . Commande::LIGNES_MAX . ' lignes attendues']);
        }

        $resultat = [];
        $erreurs = [];
        foreach ($lignes as $i => $l) {
            $ligne = is_array($l) ? $this->ligne($l) : null;
            if ($ligne === null) {
                $erreurs["lignes[{$i}]"] = 'ligne invalide : type, soiree_id ou pass_id et soirees, categorie, quantite de 1 a ' . LigneCommande::QUANTITE_MAX;
            } else {
                $resultat[] = $ligne;
            }
        }
        if ($erreurs !== []) {
            throw new RequeteInvalideException($erreurs);
        }

        return $resultat;
    }

    private function ligne(array $l): ?LigneDemandeeDto
    {
        $entier = static fn (mixed $v): ?int => is_int($v) && $v >= 1 && $v <= 999999999 ? $v : null;
        $quantite = $entier($l['quantite'] ?? null);
        $categorie = $l['categorie'] ?? null;
        if ($quantite === null || $quantite > LigneCommande::QUANTITE_MAX || !in_array($categorie, ['NORMAL', 'REDUIT'], true)) {
            return null;
        }

        if (($l['type'] ?? null) === 'SOIREE') {
            $soiree = $entier($l['soiree_id'] ?? null);

            return $soiree === null ? null : new LigneDemandeeDto('SOIREE', [$soiree], null, $categorie, $quantite);
        }
        if (($l['type'] ?? null) === 'PASS') {
            $pass = $entier($l['pass_id'] ?? null);
            $soirees = $l['soirees'] ?? null;
            if ($pass === null || !is_array($soirees) || !array_is_list($soirees) || count($soirees) > 10
                || in_array(null, array_map($entier, $soirees), true)) {
                return null;
            }

            return new LigneDemandeeDto('PASS', $soirees, $pass, $categorie, $quantite);
        }

        return null;
    }

    // { "numero": "4242 4242 4242 4242", "expiration": "12/28", "cryptogramme": "123" }
    public function carte(mixed $corps): CarteDto
    {
        $d = is_array($corps) ? $corps : [];
        $erreurs = [];

        $numero = $d['numero'] ?? null;
        if (!is_string($numero) || preg_match('/^[\d\s-]{13,23}$/', $numero) !== 1) {
            $erreurs['numero'] = 'numero de carte attendu (13 a 19 chiffres)';
        }
        $expiration = $d['expiration'] ?? null;
        if (!is_string($expiration) || preg_match('#^(0[1-9]|1[0-2])\s*/\s*(\d{2})$#', $expiration, $date) !== 1) {
            $erreurs['expiration'] = 'format MM/AA attendu';
        }
        $cryptogramme = $d['cryptogramme'] ?? null;
        if (!is_string($cryptogramme) || preg_match('/^\d{3,4}$/', $cryptogramme) !== 1) {
            $erreurs['cryptogramme'] = '3 ou 4 chiffres attendus';
        }
        if ($erreurs !== []) {
            throw new RequeteInvalideException($erreurs);
        }

        // seulement le format ici : le numero lui-meme est verifie par le domaine
        return new CarteDto($numero, (int) $date[1], 2000 + (int) $date[2], $cryptogramme);
    }
}
