<?php

declare(strict_types=1);

namespace Nrv\Infrastructure\Http\Presenter;

use Nrv\Application\Dto\UtilisateurDto;

// representation json d'un compte (sans mot de passe)
final class UtilisateurJson
{
    public static function compte(UtilisateurDto $u): array
    {
        return [
            'id' => $u->id,
            'email' => $u->email,
            'nom' => $u->nom,
            'prenom' => $u->prenom,
            'role' => $u->role,
        ];
    }
}
