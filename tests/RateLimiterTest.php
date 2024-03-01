<?php

declare(strict_types=1);

namespace Fansipan\RateLimiter\Tests;

use Fansipan\Mock\MockResponse;
use Fansipan\RateLimiter\HeaderRateLimiter;
use Fansipan\RateLimiter\Limit;
use Fansipan\RateLimiter\RateLimiter;
use Http\Discovery\Psr17FactoryDiscovery;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
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

    private function createRequest(): RequestInterface
    {
        return Psr17FactoryDiscovery::findRequestFactory()->createRequest('GET', 'http://localhost');
    }

    public function test_rate_limiter(): void
    {
        $limiter = new RateLimiter($this->cache, [
            Limit::allow(1),
        ]);

        $request = $this->createRequest();

        $this->assertFalse($limiter->tooManyAttempts($request));

        $limiter->hit($request, MockResponse::create(''));

        $this->assertTrue($limiter->tooManyAttempts($request));
    }

    public function test_multiple_rate_limiters(): void
    {
        $limiter = new RateLimiter($this->cache, [
            $hour = Limit::allow(2, 30)->withName('hour'),
            $halfDay = Limit::allow(10, 12 * 60)->withName('half-day'),
        ]);

        $request = $this->createRequest();

        $limiter->hit($request, MockResponse::create(''));
        $this->assertEquals(1, $this->cache->get($hour->key()));
        $this->assertEquals(9, $this->cache->get($halfDay->key()));

        // Let's say we fast forward the clock to 11:45 later and the first
        // limiter should reset and second limiter should have 1 remaining try.
        $this->cache->delete($hour->key());
        $this->cache->set($halfDay->key(), 1);

        $limiter->hit($request, MockResponse::create(''));

        $this->assertEquals(1, $this->cache->get($hour->key()));
        $this->assertEquals(0, $this->cache->get($halfDay->key()));

        $limiter->hit($request, MockResponse::create(''));
        $this->assertTrue($limiter->tooManyAttempts($request));
    }

    public function test_header_rate_limit(): void
    {
        $limiter = new HeaderRateLimiter($this->cache);

        $request = $this->createRequest();

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
