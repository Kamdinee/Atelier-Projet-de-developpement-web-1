<?php

declare(strict_types=1);

namespace Nrv\Tests\Domain\Entity;

use DateTimeImmutable;
use Nrv\Domain\Entity\Spectacle;
use Nrv\Domain\Exception\DonneeInvalideException;
use Nrv\Domain\ValueObject\Image;
use Nrv\Tests\Fixtures\Fabrique;
use PHPUnit\Framework\TestCase;

final class SpectacleTest extends TestCase
{
    public function testUnSpectacleAUnSeulStyle(): void
    {
        $spectacle = Fabrique::spectacle(style: Fabrique::style('blues-rock'));

        $this->assertSame('blues-rock', $spectacle->style()->slug());
    }

    public function testLImagePrincipaleEstLaPremiere(): void
    {
        $spectacle = $this->spectacle(['A'], [new Image('a.jpg'), new Image('b.jpg')]);

        $this->assertSame('a.jpg', $spectacle->imagePrincipale()?->url());
    }

    public function testSansImageIlNYAPasDImagePrincipale(): void
    {
        $spectacle = $this->spectacle(['A'], []);

        $this->assertNull($spectacle->imagePrincipale());
    }

    public function testUnSpectacleSansArtisteEstRefuse(): void
    {
        $this->expectException(DonneeInvalideException::class);

        $this->spectacle([], []);
    }

    public function testUnSpectacleSansTitreEstRefuse(): void
    {
        $this->expectException(DonneeInvalideException::class);

        new Spectacle(1, 1, '  ', 'd', Fabrique::style(), ['A'], [], null, new DateTimeImmutable());
    }

    public function testLesImagesDoiventEtreDesObjetsImage(): void
    {
        $this->expectException(DonneeInvalideException::class);

        $this->spectacle(['A'], ['pas-une-image.jpg']);
    }

    /**
     * @param list<string> $artistes
     * @param list<mixed> $images
     */
    private function spectacle(array $artistes, array $images): Spectacle
    {
        return new Spectacle(
            1,
            1,
            'Iron Lorraine',
            'description',
            Fabrique::style(),
            $artistes,
            $images,
            null,
            new DateTimeImmutable('2027-06-11 21:30'),
        );
    }
}
