<?php

declare(strict_types=1);

namespace Ecotone\Api\Tracing;

/**
 * licence Apache-2.0
 */
final class TracingConfiguration
{
    /**
     * @param string[] $spanRecorderReferenceNames
     * @param string[] $spanAttributeHeaders
     */
    private function __construct(
        public readonly array $spanRecorderReferenceNames,
        public readonly bool $spanAttributesFromAllHeaders,
        public readonly array $spanAttributeHeaders,
    ) {
    }

    public static function createWithDefaults(): self
    {
        return new self([], true, []);
    }

    public function withSpanRecorder(string $referenceName): self
    {
        return new self(...[...get_object_vars($this), 'spanRecorderReferenceNames' => [...$this->spanRecorderReferenceNames, $referenceName]]);
    }

    public function withSpanAttributesFromHeaders(string ...$headerNames): self
    {
        return new self(...[...get_object_vars($this), 'spanAttributesFromAllHeaders' => false, 'spanAttributeHeaders' => array_values($headerNames)]);
    }
}
