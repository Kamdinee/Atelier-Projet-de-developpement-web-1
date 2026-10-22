<?php

declare(strict_types=1);

namespace Nrv\Infrastructure\Http\Validation;

use Nrv\Application\Dto\NouveauSpectacleDto;
use Nrv\Application\Dto\NouvelleSoireeDto;
use Nrv\Infrastructure\Http\Exception\RequeteInvalideException;

// verifie le json des formulaires du backoffice avant les cas d'usage (400 si mal forme)
// les regles metier (2 a 3 spectacles, jauge <= capacite...) restent dans le domaine (422)
final class ProgrammeAdminValidator
{
    public const TARIF_MAX = 100000;         // 1 000,00 euros en centimes
    public const ARTISTES_MAX = 5;
    private const HEURE = '/^([01]\d|2[0-3]):[0-5]\d$/';
    private const VIDEO = '#^https://www\.youtube(-nocookie)?\.com/embed/[A-Za-z0-9_-]{11}$#';
    // image servie par le site : la politique de securite (csp) bloque les images exterieures
    private const IMAGE = '#^img/[A-Za-z0-9_-]+(/[A-Za-z0-9_-]+){0,4}\.(webp|jpe?g|png|svg)$#';

    /** @var array<string, string> */
    private array $erreurs = [];

    public function soiree(mixed $corps): NouvelleSoireeDto
    {
        $this->erreurs = [];
        $corps = $this->objet($corps, 'corps');

        $nom = $this->texte($corps, 'nom', 120);
        $thematique = $this->texte($corps, 'thematique', 80);
        $date = $this->texte($corps, 'date', 10);
        if ($date !== null && !$this->estDate($date)) {
            $this->erreurs['date'] = 'format attendu : AAAA-MM-JJ';
        }
        $heure = $this->heure($corps, 'heure_debut');
        $lieu = $this->entier($corps, 'lieu_id', 1, 999999999);
        $jauge = array_key_exists('jauge', $corps) && $corps['jauge'] !== null && $corps['jauge'] !== ''
            ? $this->entier($corps, 'jauge', 1, 1000000)
            : null;
        $normal = $this->entier($corps, 'tarif_normal', 0, self::TARIF_MAX);
        $reduit = $this->entier($corps, 'tarif_reduit', 0, self::TARIF_MAX);

        $spectacles = [];
        $liste = $corps['spectacles'] ?? null;
        if (!is_array($liste) || !array_is_list($liste) || count($liste) < 2 || count($liste) > 3) {
            $this->erreurs['spectacles'] = 'liste de 2 a 3 spectacles attendue';
        } else {
            foreach ($liste as $i => $s) {
                $spectacles[] = $this->lireSpectacle($s, "spectacles.{$i}.");
            }
        }

        $this->verifier();

        return new NouvelleSoireeDto(
            (string) $nom,
            (string) $thematique,
            (string) $date,
            (string) $heure,
            (int) $lieu,
            $jauge,
            (int) $normal,
            (int) $reduit,
            $spectacles,
        );
    }

    public function spectacle(mixed $corps): NouveauSpectacleDto
    {
        $this->erreurs = [];
        $spectacle = $this->lireSpectacle($corps, '');
        $this->verifier();

        return $spectacle;
    }

    private function lireSpectacle(mixed $s, string $prefixe): NouveauSpectacleDto
    {
        $s = $this->objet($s, rtrim($prefixe, '.') ?: 'corps');

        $titre = $this->texte($s, 'titre', 150, $prefixe);
        $description = $this->texte($s, 'description', 2000, $prefixe);
        $style = $this->texte($s, 'style', 60, $prefixe);
        if ($style !== null && preg_match('/^[a-z0-9-]+$/', $style) !== 1) {
            $this->erreurs[$prefixe . 'style'] = 'style invalide';
        }
        $horaire = $this->heure($s, 'horaire', $prefixe);

        $artistes = $s['artistes'] ?? null;
        if (is_string($artistes)) {
            // le formulaire peut envoyer "Artiste 1, Artiste 2"
            $artistes = explode(',', $artistes);
        }
        $artistes = is_array($artistes) ? array_values(array_filter(
            array_map(static fn ($a) => is_string($a) ? trim($a) : '', $artistes),
            static fn (string $a) => $a !== '',
        )) : [];
        if ($artistes === [] || count($artistes) > self::ARTISTES_MAX) {
            $this->erreurs[$prefixe . 'artistes'] = 'de 1 a ' . self::ARTISTES_MAX . ' artistes attendus';
        }
        foreach ($artistes as $a) {
            if (mb_strlen($a) > 120) {
                $this->erreurs[$prefixe . 'artistes'] = '120 caracteres au plus par artiste';
            }
        }

        $video = $this->facultatif($s, 'video', self::VIDEO, 'lien youtube embed attendu (https://www.youtube.com/embed/...)', $prefixe);
        $image = $this->facultatif($s, 'image', self::IMAGE, 'chemin d\'une image du site attendu (img/...)', $prefixe);

        return new NouveauSpectacleDto(
            (string) $titre,
            (string) $description,
            (string) $style,
            $artistes,
            (string) $horaire,
            $video,
            $image,
        );
    }

    private function verifier(): void
    {
        if ($this->erreurs !== []) {
            throw new RequeteInvalideException($this->erreurs);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function objet(mixed $valeur, string $nom): array
    {
        if (!is_array($valeur) || ($valeur !== [] && array_is_list($valeur))) {
            $this->erreurs[$nom] = 'objet json attendu';

            return [];
        }

        return $valeur;
    }

    private function texte(array $d, string $nom, int $max, string $prefixe = ''): ?string
    {
        $valeur = $d[$nom] ?? null;
        if (!is_string($valeur) || trim($valeur) === '') {
            $this->erreurs[$prefixe . $nom] = 'texte obligatoire';

            return null;
        }
        if (mb_strlen(trim($valeur)) > $max) {
            $this->erreurs[$prefixe . $nom] = "{$max} caracteres au plus";

            return null;
        }

        return trim($valeur);
    }

    private function heure(array $d, string $nom, string $prefixe = ''): ?string
    {
        $valeur = $d[$nom] ?? null;
        // accepte aussi HH:MM:SS (champ time de certains navigateurs)
        if (is_string($valeur) && preg_match('/^\d\d:\d\d:\d\d$/', $valeur) === 1) {
            $valeur = substr($valeur, 0, 5);
        }
        if (!is_string($valeur) || preg_match(self::HEURE, $valeur) !== 1) {
            $this->erreurs[$prefixe . $nom] = 'heure attendue au format HH:MM';

            return null;
        }

        return $valeur;
    }

    private function entier(array $d, string $nom, int $min, int $max): ?int
    {
        $valeur = $d[$nom] ?? null;
        if (is_string($valeur) && ctype_digit($valeur) && strlen($valeur) <= 9) {
            $valeur = (int) $valeur;
        }
        if (!is_int($valeur) || $valeur < $min || $valeur > $max) {
            $this->erreurs[$nom] = "entier entre {$min} et {$max} attendu";

            return null;
        }

        return $valeur;
    }

    private function facultatif(array $d, string $nom, string $format, string $message, string $prefixe): ?string
    {
        $valeur = $d[$nom] ?? null;
        if ($valeur === null || (is_string($valeur) && trim($valeur) === '')) {
            return null;
        }
        if (!is_string($valeur) || preg_match($format, trim($valeur)) !== 1) {
            $this->erreurs[$prefixe . $nom] = $message;

            return null;
        }

        return trim($valeur);
    }

    private function estDate(string $date): bool
    {
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $m) !== 1) {
            return false;
        }

        return checkdate((int) $m[2], (int) $m[3], (int) $m[1]);
    }
}
