<?php

declare(strict_types=1);

namespace Fansipan\RateLimiter\Tests;

use Fansipan\Mock\MockResponse;
use Fansipan\RateLimiter\HeaderRateLimiter;
use Fansipan\RateLimiter\Limit;
use Fansipan\RateLimiter\RateLimiter;
use Http\Discovery\Psr17FactoryDiscovery;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Psr16Cache;

final class RateLimiterTest extends TestCase
{
    /**
     * @var \Psr\SimpleCache\CacheInterface
     */
    private $cache;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cache = new Psr16Cache(new ArrayAdapter());
    }

    public function test_rate_limiter(): void
    {
        $limiter = new RateLimiter($this->cache, [
            Limit::allow(1),
        ]);

        $request = Psr17FactoryDiscovery::findRequestFactory()->createRequest('GET', 'http://localhost');

        $this->assertFalse($limiter->tooManyAttempts($request));

        $limiter->hit($request, MockResponse::create(''));

        $this->assertTrue($limiter->tooManyAttempts($request));
    }

    public function test_header_rate_limit(): void
    {
        $limiter = new HeaderRateLimiter($this->cache);

        $request = Psr17FactoryDiscovery::findRequestFactory()->createRequest('GET', 'http://localhost');

        $this->assertFalse($limiter->tooManyAttempts($request));

        $limiter->hit($request, MockResponse::create('', 200, [
            'X-RateLimit-Remaining' => 1,
        ]));

        $this->assertFalse($limiter->tooManyAttempts($request));

        $limiter->hit($request, MockResponse::create('', 200, [
            'X-RateLimit-Remaining' => 0,
        ]));

        $this->assertTrue($limiter->tooManyAttempts($request));
    }
}
