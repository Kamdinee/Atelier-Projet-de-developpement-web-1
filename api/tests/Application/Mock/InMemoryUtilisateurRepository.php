<?php

declare(strict_types=1);

namespace Nrv\Tests\Application\Mock;

use Nrv\Application\Port\Out\UtilisateurRepositoryInterface;
use Nrv\Domain\Entity\Utilisateur;
use Nrv\Domain\Exception\EmailDejaUtiliseException;

// faux depot de comptes en memoire
final class InMemoryUtilisateurRepository implements UtilisateurRepositoryInterface
{
    /** @var array<int, Utilisateur> */
    public array $utilisateurs = [];

    /** @param list<Utilisateur> $utilisateurs */
    public function __construct(array $utilisateurs = [])
    {
        foreach ($utilisateurs as $u) {
            $this->utilisateurs[(int) $u->id()] = $u;
        }
    }

    public function trouverParEmail(string $email): ?Utilisateur
    {
        foreach ($this->utilisateurs as $u) {
            if ($u->email() === $email) {
                return $u;
            }
        }

        return null;
    }

    public function trouverParId(int $id): ?Utilisateur
    {
        return $this->utilisateurs[$id] ?? null;
    }

    public function ajouter(Utilisateur $utilisateur): Utilisateur
    {
        if ($this->trouverParEmail($utilisateur->email()) !== null) {
            throw new EmailDejaUtiliseException();
        }
        $enregistre = $utilisateur->avecId(count($this->utilisateurs) + 1);
        $this->utilisateurs[(int) $enregistre->id()] = $enregistre;

        return $enregistre;
    }
}
