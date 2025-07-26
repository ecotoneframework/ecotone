<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Attribute;

use Attribute;
use Ecotone\Messaging\Support\Assert;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
/**
 * licence Apache-2.0
 */
final class Deduplicated
{
    public function __construct(private string $deduplicationHeaderName = '', private ?string $expression = null, private ?string $trackingName = null)
    {
        Assert::isTrue(
            ($this->deduplicationHeaderName === '' && $this->expression !== null)
            || ($this->deduplicationHeaderName !== '' && $this->expression === null)
            || ($this->deduplicationHeaderName === '' && $this->expression === null),
            'Either deduplicationHeaderName or expression should be provided for Deduplicated attribute, not both'
        );
    }

    public function getDeduplicationHeaderName(): string
    {
        return $this->deduplicationHeaderName;
    }

    public function getExpression(): ?string
    {
        return $this->expression;
    }

    public function hasExpression(): bool
    {
        return $this->expression !== null;
    }

    public function getTrackingName(): ?string
    {
        return $this->trackingName;
    }
}
