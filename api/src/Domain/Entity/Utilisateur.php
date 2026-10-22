<?php

declare(strict_types=1);

namespace Nrv\Domain\Entity;

use Nrv\Domain\Exception\DonneeInvalideException;
use Nrv\Domain\ValueObject\Role;

// un compte : obligatoire pour commander ; le mot de passe n'est connu que hache
final class Utilisateur
{
    public const NOM_MAX = 80;
    public const EMAIL_MAX = 180;

    private readonly string $email;
    private readonly string $nom;
    private readonly string $prenom;

    public function __construct(
        private readonly ?int $id,
        string $email,
        string $nom,
        string $prenom,
        private readonly string $motDePasseHache,
        private readonly Role $role = Role::Client,
    ) {
        $email = mb_strtolower(trim($email));
        $nom = trim($nom);
        $prenom = trim($prenom);

        if (mb_strlen($email) > self::EMAIL_MAX || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new DonneeInvalideException('adresse email invalide');
        }
        if ($nom === '' || $prenom === '' || mb_strlen($nom) > self::NOM_MAX || mb_strlen($prenom) > self::NOM_MAX) {
            throw new DonneeInvalideException('le nom et le prenom font de 1 a ' . self::NOM_MAX . ' caracteres');
        }
        if ($motDePasseHache === '') {
            throw new DonneeInvalideException('mot de passe manquant');
        }

        $this->email = $email;
        $this->nom = $nom;
        $this->prenom = $prenom;
    }

    // le meme compte une fois enregistre en base
    public function avecId(int $id): self
    {
        return new self($id, $this->email, $this->nom, $this->prenom, $this->motDePasseHache, $this->role);
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function nom(): string
    {
        return $this->nom;
    }

    public function prenom(): string
    {
        return $this->prenom;
    }

    public function motDePasseHache(): string
    {
        return $this->motDePasseHache;
    }

    public function role(): Role
    {
        return $this->role;
    }

    public function estAdmin(): bool
    {
        return $this->role === Role::Admin;
    }
}
