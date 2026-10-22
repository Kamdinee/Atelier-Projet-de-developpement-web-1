<?php

declare(strict_types=1);

namespace Nrv\Application\UseCase;

use DateTimeImmutable;
use Nrv\Application\Dto\NouveauSpectacleDto;
use Nrv\Application\Dto\NouvelleSoireeDto;
use Nrv\Application\Dto\ReferentielsDto;
use Nrv\Application\Dto\StyleDto;
use Nrv\Application\Port\In\GererProgrammeInterface;
use Nrv\Application\Port\Out\ProgrammeAdminRepositoryInterface;
use Nrv\Application\Port\Out\SoireeRepositoryInterface;
use Nrv\Domain\Entity\Lieu;
use Nrv\Domain\Entity\Soiree;
use Nrv\Domain\Entity\Spectacle;
use Nrv\Domain\Entity\Style;
use Nrv\Domain\Exception\DateDejaPriseException;
use Nrv\Domain\Exception\DonneeInvalideException;
use Nrv\Domain\Exception\SoireeIntrouvableException;
use Nrv\Domain\ValueObject\Image;
use Nrv\Domain\ValueObject\Tarifs;

// ajout de soirees et de spectacles : les regles du domaine (2 a 3 spectacles,
// jauge <= capacite, tarifs, un style par spectacle) sont verifiees en construisant les entites
final class GererProgramme implements GererProgrammeInterface
{
    // id provisoire des entites pas encore enregistrees
    private const NOUVEAU = 0;

    public function __construct(
        private readonly ProgrammeAdminRepositoryInterface $programme,
        private readonly SoireeRepositoryInterface $soirees,
    ) {
    }

    public function referentiels(): ReferentielsDto
    {
        return new ReferentielsDto(
            array_map(static fn (Lieu $l): array => [
                'id' => $l->id(),
                'nom' => $l->nom(),
                'adresse' => $l->adresse(),
                'capacite' => $l->capacite(),
            ], $this->programme->lieux()),
            array_map(static fn (Style $s): StyleDto => StyleDto::depuis($s), $this->programme->styles()),
        );
    }

    public function ajouterSoiree(NouvelleSoireeDto $saisie): int
    {
        $lieu = $this->programme->lieu($saisie->lieuId)
            ?? throw new DonneeInvalideException("lieu {$saisie->lieuId} inconnu");

        $debut = new DateTimeImmutable("{$saisie->date} {$saisie->heureDebut}");
        if ($this->programme->dateOccupee($debut)) {
            throw DateDejaPriseException::pour($saisie->date);
        }

        $spectacles = array_map(
            fn (NouveauSpectacleDto $s): Spectacle => $this->spectacle(self::NOUVEAU, $saisie->date, $s),
            $saisie->spectacles,
        );

        // la jauge par defaut est la capacite du lieu
        $soiree = new Soiree(
            self::NOUVEAU,
            trim($saisie->nom),
            trim($saisie->thematique),
            $debut,
            $lieu,
            new Tarifs($saisie->tarifNormal, $saisie->tarifReduit),
            $saisie->jauge ?? $lieu->capacite(),
            0,
            $spectacles,
        );

        return $this->programme->ajouterSoiree($soiree);
    }

    public function ajouterSpectacle(int $soireeId, NouveauSpectacleDto $saisie): int
    {
        $soiree = $this->soirees->trouverParId($soireeId) ?? throw SoireeIntrouvableException::avecId($soireeId);
        $spectacle = $this->spectacle($soireeId, $soiree->debut()->format('Y-m-d'), $saisie);

        // on reconstruit la soiree avec le nouveau spectacle : refus au-dela de 3
        new Soiree(
            $soiree->id(),
            $soiree->nom(),
            $soiree->thematique(),
            $soiree->debut(),
            $soiree->lieu(),
            $soiree->tarifs(),
            $soiree->jauge(),
            $soiree->placesVendues(),
            [...$soiree->spectacles(), $spectacle],
        );

        return $this->programme->ajouterSpectacle($spectacle);
    }

    private function spectacle(int $soireeId, string $date, NouveauSpectacleDto $s): Spectacle
    {
        $style = $this->programme->styleParSlug($s->style)
            ?? throw new DonneeInvalideException("style {$s->style} inconnu");
        $titre = trim($s->titre);

        return new Spectacle(
            self::NOUVEAU,
            $soireeId,
            $titre,
            trim($s->description),
            $style,
            array_values(array_filter(array_map('trim', $s->artistes), static fn (string $a) => $a !== '')),
            $s->image !== null ? [new Image($s->image, "{$titre} en concert")] : [],
            $s->video,
            new DateTimeImmutable("{$date} {$s->horaire}"),
        );
    }
}
