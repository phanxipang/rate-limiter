<?php

declare(strict_types=1);

namespace Fansipan\RateLimiter;

final class Limit
{
    /**
     * @var int<0, max>
     */
    private $maxAttempts;

    /**
     * @var int<0, max>
     */
    private $expiresAfter;

    /**
     * @var float
     */
    private $threshold;

    /**
     * @var string
     */
    private $name;

    public function __construct(
        int $maxAttempts,
        int $expiresAfterMs,
        float $threshold = 1,
        ?string $name = null
    ) {
        if ($maxAttempts < 1) {
            // @codeCoverageIgnoreStart
            throw new \InvalidArgumentException(sprintf('Max attempts must be greater than or equal to one: "%d" given.', $maxAttempts));
            // @codeCoverageIgnoreEnd
        }

        $this->maxAttempts = $maxAttempts;

        if ($expiresAfterMs < 1) {
            // @codeCoverageIgnoreStart
            throw new \InvalidArgumentException(sprintf('Expire must be greater than or equal to one: "%d" given.', $expiresAfterMs));
            // @codeCoverageIgnoreEnd
        }

        $this->expiresAfter = $expiresAfterMs;

        if ($threshold < 0 || $threshold > 1) {
            // @codeCoverageIgnoreStart
            throw new \InvalidArgumentException(sprintf('Threshold must be between zero and one: "%s" given.', $expiresAfterMs));
            // @codeCoverageIgnoreEnd
        }

        $this->threshold = $threshold;
        $this->name = $name ?? \microtime();
    }

    public static function allow(int $maxAttempts, int $expiresMinutes = 60): self
    {
        return new self($maxAttempts, $expiresMinutes * 60);
    }

    /**
     * Get the limit key for caching.
     */
    public function key(): string
    {
        $prefix = 'fansipan_rate_limit';

        return $prefix.'_'.$this->name;
    }

    /**
     * Get the limit max request attempts.
     */
    public function maxAttempts(bool $includeThreshold = true): int
    {
        return $includeThreshold
            ? (int) \round($this->maxAttempts * $this->threshold, \PHP_ROUND_HALF_DOWN)
            : $this->maxAttempts;
    }

    /**
     * Get the expiration time in millisecond.
     */
    public function expiresAfter(): int
    {
        return $this->expiresAfter;
    }

    /**
     * Set a custom name for the limit.
     */
    public function withName(string $name): self
    {
        $clone = clone $this;
        $clone->name = $name;

        return $clone;
    }
}
