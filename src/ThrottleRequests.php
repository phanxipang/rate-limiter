<?php

declare(strict_types=1);

namespace Fansipan\RateLimiter;

use Fansipan\RateLimiter\Exception\RateLimitReachedException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class ThrottleRequests
{
    /**
     * @var RateLimiterInterface
     */
    private $limiter;

    public function __construct(RateLimiterInterface $limiter)
    {
        $this->limiter = $limiter;
    }

    public function __invoke(RequestInterface $request, callable $next): ResponseInterface
    {
        if ($this->limiter->tooManyAttempts($request)) {
            throw new RateLimitReachedException('Request Rate Limit Reached', $request);
        }

        $response = $next($request);

        $this->limiter->hit($request, $response);

        return $response;
    }
}
