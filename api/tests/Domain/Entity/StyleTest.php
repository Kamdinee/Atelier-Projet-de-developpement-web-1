<?php

declare(strict_types=1);

namespace Nrv\Tests\Domain\Entity;

use Nrv\Domain\Entity\Style;
use Nrv\Domain\Exception\DonneeInvalideException;
use PHPUnit\Framework\TestCase;

final class StyleTest extends TestCase
{
    public function testUnStyleValide(): void
    {
        $style = new Style(1, 'Classic rock', 'classic-rock');

        $this->assertSame('classic-rock', $style->slug());
        $this->assertSame('Classic rock', $style->libelle());
    }

    public function testUnSlugAvecMajusculesOuEspacesEstRefuse(): void
    {
        $this->expectException(DonneeInvalideException::class);

        new Style(1, 'Classic rock', 'Classic Rock');
    }
}
