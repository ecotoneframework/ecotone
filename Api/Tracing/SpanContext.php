<?php

declare(strict_types=1);

namespace Ecotone\Api\Tracing;

use function bin2hex;
use function random_bytes;
use function sprintf;

/**
 * licence Apache-2.0
 */
final class SpanContext
{
    private const TRACEPARENT_PATTERN = '/^00-([0-9a-f]{32})-([0-9a-f]{16})-([0-9a-f]{2})$/';
    private const SAMPLED_FLAG = 0x01;

    public function __construct(
        public readonly string $traceId,
        public readonly string $spanId,
        public readonly bool $sampled = true,
        public readonly string $traceState = '',
    ) {
    }

    public static function newTrace(): self
    {
        return new self(bin2hex(random_bytes(16)), bin2hex(random_bytes(8)));
    }

    public function newChild(): self
    {
        return new self($this->traceId, bin2hex(random_bytes(8)), $this->sampled, $this->traceState);
    }

    public function withTraceState(string $traceState): self
    {
        return new self($this->traceId, $this->spanId, $this->sampled, $traceState);
    }

    public static function fromTraceparent(?string $traceparent, string $tracestate = ''): ?self
    {
        if ($traceparent === null || preg_match(self::TRACEPARENT_PATTERN, $traceparent, $matches) !== 1) {
            return null;
        }
        if ($matches[1] === str_repeat('0', 32) || $matches[2] === str_repeat('0', 16)) {
            return null;
        }

        return new self($matches[1], $matches[2], (hexdec($matches[3]) & self::SAMPLED_FLAG) === self::SAMPLED_FLAG, $tracestate);
    }

    public function toTraceparent(): string
    {
        return sprintf('00-%s-%s-%02x', $this->traceId, $this->spanId, $this->sampled ? self::SAMPLED_FLAG : 0);
    }
}
