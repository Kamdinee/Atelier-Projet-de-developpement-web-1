<?php

declare(strict_types=1);

namespace Nrv\Infrastructure\Http;

use Psr\Http\Message\ResponseInterface;

// ecrit une reponse json
final class Json
{
    public static function reponse(
        ResponseInterface $response,
        array $data,
        int $statut = 200,
        string $type = 'application/json',
    ): ResponseInterface {
        $response->getBody()->write(
            (string) json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)
        );

        return $response
            ->withStatus($statut)
            ->withHeader('Content-Type', $type . '; charset=utf-8');
    }
}
