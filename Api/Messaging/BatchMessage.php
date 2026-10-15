<?php

declare(strict_types=1);

namespace Ecotone\Api\Messaging;

use Countable;

/**
 * licence Apache-2.0
 */
final class BatchMessage implements Countable
{
    /**
     * @param array<int, array{payload: mixed, headers: array<string, mixed>}> $entries
     */
    private function __construct(public readonly array $entries = [])
    {
    }

    public static function constructEmpty(): self
    {
        return new self();
    }

    /**
     * @param array<int, array{payload: mixed, headers: array<string, mixed>}> $entries
     */
    public static function fromEntries(array $entries): self
    {
        return new self(array_values($entries));
    }

    /**
     * @param array<string, mixed> $headers
     */
    public function append(mixed $payload, array $headers = []): self
    {
        return new self(...[...get_object_vars($this), 'entries' => [...$this->entries, ['payload' => $payload, 'headers' => $headers]]]);
    }

    public function count(): int
    {
        return count($this->entries);
    }
}
