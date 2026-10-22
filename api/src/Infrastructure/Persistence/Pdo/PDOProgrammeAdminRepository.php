<?php

declare(strict_types=1);

namespace Nrv\Infrastructure\Persistence\Pdo;

use DateTimeImmutable;
use Nrv\Application\Port\Out\ProgrammeAdminRepositoryInterface;
use Nrv\Domain\Entity\Lieu;
use Nrv\Domain\Entity\Soiree;
use Nrv\Domain\Entity\Spectacle;
use Nrv\Domain\Entity\Style;
use PDO;
use Throwable;

// adaptateur pdo du port ProgrammeAdminRepositoryInterface
final class PDOProgrammeAdminRepository implements ProgrammeAdminRepositoryInterface
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function lieu(int $id): ?Lieu
    {
        $requete = $this->pdo->prepare('SELECT id, nom, adresse, capacite FROM lieux WHERE id = :id');
        $requete->bindValue('id', $id, PDO::PARAM_INT);
        $requete->execute();
        $l = $requete->fetch(PDO::FETCH_ASSOC);

        return $l === false ? null : $this->versLieu($l);
    }

    public function lieux(): array
    {
        $requete = $this->pdo->prepare('SELECT id, nom, adresse, capacite FROM lieux ORDER BY nom');
        $requete->execute();

        return array_map(fn (array $l): Lieu => $this->versLieu($l), $requete->fetchAll(PDO::FETCH_ASSOC));
    }

    public function styleParSlug(string $slug): ?Style
    {
        $requete = $this->pdo->prepare('SELECT id, libelle, slug FROM styles WHERE slug = :slug');
        $requete->bindValue('slug', $slug, PDO::PARAM_STR);
        $requete->execute();
        $l = $requete->fetch(PDO::FETCH_ASSOC);

        return $l === false ? null : new Style((int) $l['id'], (string) $l['libelle'], (string) $l['slug']);
    }

    public function styles(): array
    {
        $requete = $this->pdo->prepare('SELECT id, libelle, slug FROM styles ORDER BY libelle');
        $requete->execute();

        return array_map(
            static fn (array $l): Style => new Style((int) $l['id'], (string) $l['libelle'], (string) $l['slug']),
            $requete->fetchAll(PDO::FETCH_ASSOC),
        );
    }

    public function dateOccupee(DateTimeImmutable $date): bool
    {
        $requete = $this->pdo->prepare('SELECT COUNT(*) FROM soirees WHERE date_soiree = :date');
        $requete->bindValue('date', $date->format('Y-m-d'), PDO::PARAM_STR);
        $requete->execute();

        return (int) $requete->fetchColumn() > 0;
    }

    public function ajouterSoiree(Soiree $soiree): int
    {
        $this->pdo->beginTransaction();
        try {
            $requete = $this->pdo->prepare(
                'INSERT INTO soirees (nom, thematique, date_soiree, heure_debut, lieu_id, jauge, places_vendues,
                    tarif_normal, tarif_reduit)
                VALUES (:nom, :thematique, :date, :heure, :lieu, :jauge, 0, :normal, :reduit)'
            );
            $requete->bindValue('nom', $soiree->nom());
            $requete->bindValue('thematique', $soiree->thematique());
            $requete->bindValue('date', $soiree->debut()->format('Y-m-d'));
            $requete->bindValue('heure', $soiree->debut()->format('H:i:s'));
            $requete->bindValue('lieu', $soiree->lieu()->id(), PDO::PARAM_INT);
            $requete->bindValue('jauge', $soiree->jauge(), PDO::PARAM_INT);
            $requete->bindValue('normal', $soiree->tarifs()->normal(), PDO::PARAM_INT);
            $requete->bindValue('reduit', $soiree->tarifs()->reduit(), PDO::PARAM_INT);
            $requete->execute();
            $soireeId = (int) $this->pdo->lastInsertId();

            foreach ($soiree->spectacles() as $spectacle) {
                $this->insererSpectacle($soireeId, $spectacle);
            }

            $this->pdo->commit();

            return $soireeId;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function ajouterSpectacle(Spectacle $spectacle): int
    {
        $this->pdo->beginTransaction();
        try {
            $id = $this->insererSpectacle($spectacle->soireeId(), $spectacle);
            $this->pdo->commit();

            return $id;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    // le spectacle, ses artistes (crees s'ils sont nouveaux) et ses images
    private function insererSpectacle(int $soireeId, Spectacle $spectacle): int
    {
        $requete = $this->pdo->prepare(
            'INSERT INTO spectacles (soiree_id, style_id, titre, description, video_url, horaire_previsionnel)
            VALUES (:soiree, :style, :titre, :description, :video, :horaire)'
        );
        $requete->bindValue('soiree', $soireeId, PDO::PARAM_INT);
        $requete->bindValue('style', $spectacle->style()->id(), PDO::PARAM_INT);
        $requete->bindValue('titre', $spectacle->titre());
        $requete->bindValue('description', $spectacle->description());
        $requete->bindValue('video', $spectacle->videoUrl(), $spectacle->videoUrl() === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $requete->bindValue('horaire', $spectacle->horaire()->format('H:i:s'));
        $requete->execute();
        $spectacleId = (int) $this->pdo->lastInsertId();

        $lien = $this->pdo->prepare(
            'INSERT INTO spectacle_artiste (spectacle_id, artiste_id, ordre) VALUES (:spectacle, :artiste, :ordre)'
        );
        foreach ($spectacle->artistes() as $ordre => $nom) {
            $lien->bindValue('spectacle', $spectacleId, PDO::PARAM_INT);
            $lien->bindValue('artiste', $this->artiste($nom), PDO::PARAM_INT);
            $lien->bindValue('ordre', $ordre + 1, PDO::PARAM_INT);
            $lien->execute();
        }

        $image = $this->pdo->prepare(
            'INSERT INTO spectacle_images (spectacle_id, url, alt, ordre) VALUES (:spectacle, :url, :alt, :ordre)'
        );
        foreach ($spectacle->images() as $ordre => $img) {
            $image->bindValue('spectacle', $spectacleId, PDO::PARAM_INT);
            $image->bindValue('url', $img->url());
            $image->bindValue('alt', $img->alt());
            $image->bindValue('ordre', $ordre + 1, PDO::PARAM_INT);
            $image->execute();
        }

        return $spectacleId;
    }

    // id de l'artiste, cree s'il n'existe pas encore (nom unique)
    private function artiste(string $nom): int
    {
        $requete = $this->pdo->prepare('SELECT id FROM artistes WHERE nom = :nom');
        $requete->bindValue('nom', $nom);
        $requete->execute();
        $id = $requete->fetchColumn();
        if ($id !== false) {
            return (int) $id;
        }

        $insertion = $this->pdo->prepare('INSERT INTO artistes (nom) VALUES (:nom)');
        $insertion->bindValue('nom', $nom);
        $insertion->execute();

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * @param array<string, mixed> $l
     */
    private function versLieu(array $l): Lieu
    {
        return new Lieu((int) $l['id'], (string) $l['nom'], (string) $l['adresse'], (int) $l['capacite']);
    }
}
