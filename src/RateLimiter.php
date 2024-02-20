<?php

declare(strict_types=1);

namespace Fansipan\RateLimiter;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\CacheInterface;

final class RateLimiter implements RateLimiterInterface
{
    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var iterable<Limit>
     */
    private $limits;

    /**
     * @param  iterable<Limit> $limits
     */
    public function __construct(CacheInterface $cache, iterable $limits)
    {
        $this->cache = $cache;
        $this->limits = $limits;
    }

    public function tooManyAttempts(RequestInterface $request): bool
    {
        foreach ($this->limits as $limit) {
            $remaining = $this->cache->get($limit->key());

            if (! \is_numeric($remaining)) {
                continue;
            }

            if ($remaining === 0) {
                return true;
            }
        }

        return false;
    }

    public function hit(RequestInterface $request, ResponseInterface $response): void
    {
        foreach ($this->limits as $limit) {
            $key = $limit->key();
            $remaining = $this->cache->get($key);

            if (! \is_numeric($remaining)) {
                $remaining = $limit->maxAttempts();
            }

            $this->cache->set($key, --$remaining);
        }
    }
}
