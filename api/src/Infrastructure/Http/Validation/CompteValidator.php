<?php

declare(strict_types=1);

namespace Nrv\Infrastructure\Http\Validation;

use Nrv\Application\UseCase\InscrireUtilisateur;
use Nrv\Domain\Entity\Utilisateur;
use Nrv\Infrastructure\Http\Exception\RequeteInvalideException;

// verifie le corps json des routes /auth/register et /auth/signin (un message par champ)
final class CompteValidator
{
    /**
     * @return array{email: string, nom: string, prenom: string, mot_de_passe: string}
     */
    public function inscription(mixed $corps): array
    {
        $donnees = $this->objet($corps);
        $erreurs = [];

        $email = $this->texte($donnees, 'email', $erreurs);
        if ($email !== null && (mb_strlen($email) > Utilisateur::EMAIL_MAX || filter_var($email, FILTER_VALIDATE_EMAIL) === false)) {
            $erreurs['email'] = 'adresse email invalide';
        }
        foreach (['nom', 'prenom'] as $champ) {
            $valeur = $this->texte($donnees, $champ, $erreurs);
            if ($valeur !== null && mb_strlen($valeur) > Utilisateur::NOM_MAX) {
                $erreurs[$champ] = Utilisateur::NOM_MAX . ' caracteres au maximum';
            }
        }
        $motDePasse = $donnees['mot_de_passe'] ?? null;
        $min = InscrireUtilisateur::MOT_DE_PASSE_MIN;
        $max = InscrireUtilisateur::MOT_DE_PASSE_MAX;
        if (!is_string($motDePasse) || strlen($motDePasse) < $min || strlen($motDePasse) > $max) {
            $erreurs['mot_de_passe'] = "de {$min} a {$max} caracteres";
        }

        if ($erreurs !== []) {
            throw new RequeteInvalideException($erreurs);
        }

        return [
            'email' => trim($donnees['email']),
            'nom' => trim($donnees['nom']),
            'prenom' => trim($donnees['prenom']),
            'mot_de_passe' => $motDePasse,
        ];
    }

    /**
     * @return array{email: string, mot_de_passe: string}
     */
    public function connexion(mixed $corps): array
    {
        $donnees = $this->objet($corps);
        $erreurs = [];

        $this->texte($donnees, 'email', $erreurs);
        $motDePasse = $donnees['mot_de_passe'] ?? null;
        if (!is_string($motDePasse) || $motDePasse === '' || strlen($motDePasse) > 255) {
            $erreurs['mot_de_passe'] = 'mot de passe obligatoire';
        }

        if ($erreurs !== []) {
            throw new RequeteInvalideException($erreurs);
        }

        return ['email' => trim($donnees['email']), 'mot_de_passe' => $motDePasse];
    }

    private function objet(mixed $corps): array
    {
        if (!is_array($corps)) {
            throw new RequeteInvalideException(['corps' => 'objet json attendu (Content-Type: application/json)']);
        }

        return $corps;
    }

    // champ texte obligatoire, 255 caracteres au plus avant les regles propres a chaque champ
    private function texte(array $donnees, string $champ, array &$erreurs): ?string
    {
        $valeur = $donnees[$champ] ?? null;
        if (!is_string($valeur) || trim($valeur) === '' || mb_strlen($valeur) > 255) {
            $erreurs[$champ] = 'champ obligatoire';

            return null;
        }

        return trim($valeur);
    }
}
