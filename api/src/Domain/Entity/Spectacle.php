<?php

declare(strict_types=1);

namespace Nrv\Domain\Entity;

use DateTimeImmutable;
use Nrv\Domain\Exception\DonneeInvalideException;
use Nrv\Domain\ValueObject\Image;

// un concert d'une soiree, avec un seul style de musique
final class Spectacle
{
    /** @var list<string> */
    private readonly array $artistes;

    /** @var list<Image> */
    private readonly array $images;

    /**
     * @param list<string> $artistes
     * @param list<Image> $images
     */
    public function __construct(
        private readonly int $id,
        private readonly int $soireeId,
        private readonly string $titre,
        private readonly string $description,
        private readonly Style $style,
        array $artistes,
        array $images,
        private readonly ?string $videoUrl,
        private readonly DateTimeImmutable $horaire,
    ) {
        if (trim($titre) === '') {
            throw new DonneeInvalideException('un spectacle doit avoir un titre');
        }
        if ($artistes === []) {
            throw new DonneeInvalideException("le spectacle {$titre} doit avoir au moins un artiste");
        }
        foreach ($images as $image) {
            if (!$image instanceof Image) {
                throw new DonneeInvalideException('les images doivent etre des objets Image');
            }
        }

        $this->artistes = array_values($artistes);
        $this->images = array_values($images);
    }

    public function id(): int
    {
        return $this->id;
    }

    public function soireeId(): int
    {
        return $this->soireeId;
    }

    public function titre(): string
    {
        return $this->titre;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function style(): Style
    {
        return $this->style;
    }

    /**
     * @return list<string>
     */
    public function artistes(): array
    {
        return $this->artistes;
    }

    /**
     * @return list<Image>
     */
    public function images(): array
    {
        return $this->images;
    }

    // premiere image, utilisee dans la grille du programme
    public function imagePrincipale(): ?Image
    {
        return $this->images[0] ?? null;
    }

    public function videoUrl(): ?string
    {
        return $this->videoUrl;
    }

    // date et heure prevues du spectacle
    public function horaire(): DateTimeImmutable
    {
        return $this->horaire;
    }
}
