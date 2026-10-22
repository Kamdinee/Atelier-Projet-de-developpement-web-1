<?php

declare(strict_types=1);

namespace Nrv\Tests\Domain\ValueObject;

use Nrv\Domain\Exception\DonneeInvalideException;
use Nrv\Domain\ValueObject\JetonBillet;
use PHPUnit\Framework\TestCase;

final class JetonBilletTest extends TestCase
{
    public function testAccepteUnUuidV4EtLeMetEnMinuscules(): void
    {
        $jeton = new JetonBillet('9F1C2D7A-5E3B-4F60-8C0A-6B2E4D1F9A33');

        $this->assertSame('9f1c2d7a-5e3b-4f60-8c0a-6b2e4d1f9a33', $jeton->valeur());
    }

    public function testRefuseCeQuiNestPasUnUuidV4(): void
    {
        $this->assertFalse(JetonBillet::estValide("' OR 1=1 --"));
        $this->assertFalse(JetonBillet::estValide('9f1c2d7a-5e3b-1f60-8c0a-6b2e4d1f9a33'));

        $this->expectException(DonneeInvalideException::class);
        new JetonBillet('abc');
    }
}
