<?php

declare(strict_types=1);

namespace Fansipan\RateLimiter\Tests;

use Fansipan\ConnectorConfigurator;
use Fansipan\GenericConnector;
use Fansipan\Mock\MockClient;
use Fansipan\Mock\MockResponse;
use Fansipan\RateLimiter\Exception\RateLimitReachedException;
use Fansipan\RateLimiter\HeaderRateLimiter;
use Fansipan\RateLimiter\Limit;
use Fansipan\RateLimiter\RateLimiter;
use Fansipan\RateLimiter\ThrottleRequests;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Psr16Cache;

final class MiddlewareTest extends TestCase
{
    /**
     * @var \Psr\SimpleCache\CacheInterface
     */
    private $cache;

    /**
     * @var GenericConnector
     */
    private $connector;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cache = new Psr16Cache(new ArrayAdapter());
        $this->connector = new GenericConnector();
    }

    public function test_throttle_requests_middleware_with_rate_limiter(): void
    {
        $limiter = new RateLimiter($this->cache, [
            Limit::allow(2),
        ]);

        $connector = (new ConnectorConfigurator())
            ->middleware(new ThrottleRequests($limiter), 'rate_limiter')
            ->configure($this->connector->withClient(new MockClient()));

        $this->assertCount(1, $connector->middleware());

        $response = $connector->send(new DummyRequest());

        $this->assertTrue($response->ok());

        $connector->send(new DummyRequest());

        $this->expectException(RateLimitReachedException::class);

        $connector->send(new DummyRequest());
    }

    public function test_throttle_requests_middleware_with_header_rate_limiter(): void
    {
        $limiter = new HeaderRateLimiter($this->cache);
        $response = MockResponse::create('');

        $responses = static function (int $remaining) use ($response) {
            for ($i = --$remaining; $i >= 0; --$i) {
                yield $response->withAddedHeader('X-RateLimit-Remaining', $i);
            }
        };

        $client = new MockClient($responses(2));

        $connector = (new ConnectorConfigurator())
            ->middleware(new ThrottleRequests($limiter), 'rate_limiter')
            ->configure($this->connector->withClient($client));

        $this->assertCount(1, $connector->middleware());

        $response = $connector->send(new DummyRequest());

        $this->assertTrue($response->ok());
        $this->assertSame(1, (int) $response->header('X-RateLimit-Remaining'));

        $connector->send(new DummyRequest());

        $this->expectException(RateLimitReachedException::class);

        $connector->send(new DummyRequest());
        dump($this->cache);
    }
}
