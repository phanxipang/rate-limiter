<?php

declare(strict_types=1);

namespace Fansipan\RateLimiter;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\CacheInterface;

final class HeaderRateLimiter implements RateLimiterInterface
{
    private const CACHE_KEY = 'fansipan_header_rate_limiter';

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var string
     */
    private $cacheKey;

    /**
     * @var string
     */
    private $remainingHeader;

    /**
     * @var string
     */
    private $expiresAfterHeader;

    public function __construct(
        CacheInterface $cache,
        string $remainingHeader = 'X-RateLimit-Remaining',
        string $expiresAfterHeader = 'Retry-After'
    ) {
        $this->cache = $cache;
        $this->cacheKey = self::CACHE_KEY;
        $this->remainingHeader = $remainingHeader;
        $this->expiresAfterHeader = $expiresAfterHeader;
    }

    public function tooManyAttempts(RequestInterface $request): bool
    {
        $remaining = $this->cache->get($this->cacheKey);

        if (\is_null($remaining)) {
            return false;
        }

        return (int) $remaining === 0;
    }

    public function hit(RequestInterface $request, ResponseInterface $response): void
    {
        $retryAfter = $response->getHeaderLine($this->expiresAfterHeader);

        $this->cache->set(
            $this->cacheKey,
            (int) $response->getHeaderLine($this->remainingHeader),
            $retryAfter ? (int) $retryAfter : null
        );
    }
}
