<?php

declare(strict_types=1);

namespace Ecotone\Messaging;

use Countable;

/**
 * licence Apache-2.0
 */
final class BatchMessage implements Countable
{
    /** @var array<int, array{payload: mixed, headers: array<string, mixed>}> */
    private array $entries = [];

    private function __construct()
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
        $batchMessage = new self();
        $batchMessage->entries = array_values($entries);

        return $batchMessage;
    }

    /**
     * @param array<string, mixed> $headers
     */
    public function append(mixed $payload, array $headers = []): self
    {
        $appended = clone $this;
        $appended->entries[] = ['payload' => $payload, 'headers' => $headers];

        return $appended;
    }

    /**
     * @return array<int, array{payload: mixed, headers: array<string, mixed>}>
     */
    public function getEntries(): array
    {
        return $this->entries;
    }

    public function count(): int
    {
        return count($this->entries);
    }
}
