<?php

declare(strict_types=1);

namespace Ecotone\Api\Console;

/**
 * licence Apache-2.0
 */
final class ConsoleCommandResultSet
{
    /**
     * @param string[] $columnHeaders
     * @param array<array<string|int|float|bool|null>> $rows
     */
    private function __construct(public readonly array $columnHeaders, public readonly array $rows)
    {
    }

    /**
     * @param string[] $columnHeaders
     * @param array<array<string|int|float|bool|null>> $rows
     */
    public static function create(array $columnHeaders, array $rows): self
    {
        return new self($columnHeaders, $rows);
    }
}
