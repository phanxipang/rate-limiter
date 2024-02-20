<?php

declare(strict_types=1);

namespace Fansipan\RateLimiter\Tests;

use Fansipan\Request;

final class DummyRequest extends Request
{
    private $uri;

    private $method;

    public function __construct(
        string $uri = 'https://example.com',
        string $method = 'GET'
    ) {
        $this->uri = $uri;
        $this->method = $method;
    }

    public function method(): string
    {
        return $this->method;
    }

    public function endpoint(): string
    {
        return $this->uri;
    }
}
