<?php

declare(strict_types=1);

namespace Coffeemakr\Mial\Exception;


class InvalidTopLevelDomainException extends \Exception
{
    private readonly string $incorrect_tld;
    public function __construct(string|null $incorrect_tld = NULL, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct("Incorrect top level domain", $code, $previous);
        $this->incorrect_tld = $incorrect_tld;
    }
}
