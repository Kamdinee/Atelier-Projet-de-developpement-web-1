<?php

declare(strict_types=1);

namespace Nrv\Domain\ValueObject;

use Nrv\Domain\Exception\DonneeInvalideException;

final class Image
{
    public function __construct(
        private readonly string $url,
        private readonly string $alt = '',
    ) {
        if (trim($url) === '') {
            throw new DonneeInvalideException("l'url d'une image ne peut pas etre vide");
        }
    }

    public function url(): string
    {
        return $this->url;
    }

    public function alt(): string
    {
        return $this->alt;
    }
}
