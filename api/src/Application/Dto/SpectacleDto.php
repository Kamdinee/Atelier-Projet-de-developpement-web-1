<?php

declare(strict_types=1);

namespace Nrv\Application\Dto;

use Nrv\Domain\Entity\Spectacle;
use Nrv\Domain\ValueObject\Image;

// donnees d'un spectacle envoyees a l'adaptateur http
final class SpectacleDto
{
    /**
     * @param list<string> $artistes
     * @param list<array{url: string, alt: string}> $images
     */
    public function __construct(
        public readonly int $id,
        public readonly int $soireeId,
        public readonly string $titre,
        public readonly string $description,
        public readonly StyleDto $style,
        public readonly array $artistes,
        public readonly array $images,
        public readonly ?string $video,
        public readonly string $date,
        public readonly string $horaire,
    ) {
    }

    public static function depuis(Spectacle $spectacle): self
    {
        return new self(
            $spectacle->id(),
            $spectacle->soireeId(),
            $spectacle->titre(),
            $spectacle->description(),
            StyleDto::depuis($spectacle->style()),
            $spectacle->artistes(),
            array_map(
                static fn (Image $i): array => ['url' => $i->url(), 'alt' => $i->alt()],
                $spectacle->images(),
            ),
            $spectacle->videoUrl(),
            $spectacle->horaire()->format('Y-m-d'),
            $spectacle->horaire()->format('H:i'),
        );
    }

    public function imagePrincipale(): ?string
    {
        return $this->images[0]['url'] ?? null;
    }
}
