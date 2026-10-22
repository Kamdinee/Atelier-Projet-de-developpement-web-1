<?php

declare(strict_types=1);

namespace Nrv\Tests\Domain\ValueObject;

use DateTimeImmutable;
use Nrv\Domain\Exception\DonneeInvalideException;
use Nrv\Domain\ValueObject\CarteBancaire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CarteBancaireTest extends TestCase
{
    public function testUneCarteValideNeGardeQueSesQuatreDerniersChiffresAAfficher(): void
    {
        $carte = new CarteBancaire('4242 4242-4242 4242', 12, 2028, '123');

        $this->assertSame('4242', $carte->fin());
        $this->assertSame('4242424242424242', $carte->numero());
    }

    public function testLaCarteResteValableJusquALaFinDuMois(): void
    {
        $carte = new CarteBancaire('4242424242424242', 10, 2026, '123');

        $this->assertFalse($carte->estExpiree(new DateTimeImmutable('2026-10-31 23:59')));
        $this->assertTrue($carte->estExpiree(new DateTimeImmutable('2026-11-01')));
    }

    /** @return array<string, array{string, int, int, string}> */
    public static function cartesInvalides(): array
    {
        return [
            'faute de frappe (luhn)' => ['4242424242424241', 12, 2028, '123'],
            'trop court' => ['424242', 12, 2028, '123'],
            'lettres' => ['4242abcd42424242', 12, 2028, '123'],
            'mois 13' => ['4242424242424242', 13, 2028, '123'],
            'cryptogramme a 2 chiffres' => ['4242424242424242', 12, 2028, '12'],
        ];
    }

    #[DataProvider('cartesInvalides')]
    public function testUneCarteInvalideEstRefusee(string $numero, int $mois, int $annee, string $cvc): void
    {
        $this->expectException(DonneeInvalideException::class);

        new CarteBancaire($numero, $mois, $annee, $cvc);
    }
}
