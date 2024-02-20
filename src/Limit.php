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
     * @var null|string
     */
    private $name;

    public function __construct(
        int $maxAttempts,
        int $expiresAfter,
        float $threshold = 1,
        ?string $name = null
    ) {
        if ($maxAttempts < 1) {
            // @codeCoverageIgnoreStart
            throw new \InvalidArgumentException(sprintf('Max attempts must be greater than or equal to one: "%s" given.', $maxAttempts));
            // @codeCoverageIgnoreEnd
        }

        $this->maxAttempts = $maxAttempts;

        if ($expiresAfter < 1) {
            // @codeCoverageIgnoreStart
            throw new \InvalidArgumentException(sprintf('Expire must be greater than or equal to one: "%s" given.', $expiresAfter));
            // @codeCoverageIgnoreEnd
        }

        $this->expiresAfter = $expiresAfter;

        if ($threshold < 0 || $threshold > 1) {
            // @codeCoverageIgnoreStart
            throw new \InvalidArgumentException(sprintf('Threshold must be between zero and one: "%s" given.', $expiresAfter));
            // @codeCoverageIgnoreEnd
        }

        $this->threshold = $threshold;
        $this->name = $name;
    }

    public static function allow(int $maxAttempts): self
    {
        return new self($maxAttempts, 3600);
    }

    public function key(): string
    {
        $prefix = 'fansipan_rate_limit';

        return $prefix.''.$this->name;
    }

    public function maxAttempts(bool $includeThreshold = true): int
    {
        return $includeThreshold
            ? (int) \round($this->maxAttempts * $this->threshold, \PHP_ROUND_HALF_DOWN)
            : $this->maxAttempts;
    }

    public function expiresAfter(): int
    {
        return $this->expiresAfter;
    }

    public function withName(string $name): self
    {
        $clone = clone $this;
        $clone->name = $name;

        return $clone;
    }
}
