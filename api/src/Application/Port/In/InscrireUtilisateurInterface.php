<?php

declare(strict_types=1);

namespace Nrv\Application\Port\In;

use Nrv\Application\Dto\UtilisateurDto;

// cas d'usage : creer un compte client
interface InscrireUtilisateurInterface
{
    public function executer(string $email, string $nom, string $prenom, string $motDePasse): UtilisateurDto;
}
