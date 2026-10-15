<?php

declare(strict_types=1);

namespace Ecotone\Api\Attribute;

use Attribute;
use Closure;
use Ecotone\Messaging\Attribute\WithExpression;
use Ecotone\Messaging\Support\Assert;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
/**
 * licence Apache-2.0
 */
final class Deduplicated implements WithExpression
{
    public function __construct(public readonly string $deduplicationHeaderName = '', private string|Closure|null $expression = null, public readonly ?string $trackingName = null)
    {
        Assert::isTrue(
            ($this->deduplicationHeaderName === '' && $this->expression !== null)
            || ($this->deduplicationHeaderName !== '' && $this->expression === null)
            || ($this->deduplicationHeaderName === '' && $this->expression === null),
            'Either deduplicationHeaderName or expression should be provided for Deduplicated attribute, not both'
        );
    }

    public function getExpression(): string|Closure|null
    {
        return $this->expression;
    }

    public function hasExpression(): bool
    {
        return $this->expression !== null;
    }
}
