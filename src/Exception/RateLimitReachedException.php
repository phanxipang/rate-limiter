<?php

declare(strict_types=1);

namespace Fansipan\RateLimiter\Exception;

use Fansipan\Exception\RequestException;

class RateLimitReachedException extends RequestException
{
}
