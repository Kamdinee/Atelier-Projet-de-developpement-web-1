<?php

declare(strict_types=1);

namespace Nrv\Infrastructure\Token;

use Nrv\Application\Port\Out\GenerateurIdInterface;

// adaptateur du port GenerateurIdInterface : uuid v4 tire avec random_bytes (generateur cryptographique)
final class UuidGenerateur implements GenerateurIdInterface
{
    public function uuid(): string
    {
        $octets = random_bytes(16);
        $octets[6] = chr((ord($octets[6]) & 0x0f) | 0x40); // version 4
        $octets[8] = chr((ord($octets[8]) & 0x3f) | 0x80); // variante rfc 4122

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($octets), 4));
    }
}
