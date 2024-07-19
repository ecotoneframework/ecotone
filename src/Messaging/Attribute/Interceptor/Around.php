<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Attribute\Interceptor;

use Attribute;
use Ecotone\Messaging\Precedence;

#[Attribute(Attribute::TARGET_METHOD)]
/**
 * licence Apache-2.0
 */
class Around
{
    public int $precedence;

    public string $pointcut;

    public function __construct(int $precedence = Precedence::DEFAULT_PRECEDENCE, string $pointcut = '')
    {
        $this->precedence = $precedence;
        $this->pointcut   = $pointcut;
    }

    public function getPrecedence(): int
    {
        return $this->precedence;
    }

    public function getPointcut(): string
    {
        return $this->pointcut;
    }
}
