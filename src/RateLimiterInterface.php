<?php

declare(strict_types=1);

namespace Fansipan\RateLimiter;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

interface RateLimiterInterface
{
    /**
     * Determine if the request has been "sent" too many times.
     */
    public function tooManyAttempts(RequestInterface $request): bool;

    /**
     * Increment the counter of the limiter.
     */
    public function hit(RequestInterface $request, ResponseInterface $response): void;
}
