<?php

declare(strict_types=1);

namespace Nrv\Application\Port\In;

use Nrv\Application\Dto\BilletDto;
use Nrv\Application\Dto\CommandeDto;

// cas d'usage : espace client, mes commandes et mes billets
interface ConsulterMesAchatsInterface
{
    /**
     * @return list<CommandeDto>
     */
    public function commandes(int $utilisateurId): array;

    /**
     * billets d'une de mes commandes (introuvable si elle appartient a un autre client)
     *
     * @return list<BilletDto>
     */
    public function billetsDeCommande(int $utilisateurId, string $commandeId): array;

    /**
     * @return list<BilletDto>
     */
    public function billets(int $utilisateurId): array;
}
