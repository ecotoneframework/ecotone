<?php

namespace Ecotone\Messaging\Config;

class ConsoleCommandResultSet
{
    private array $columnHeaders;
    private array $rows;

    private function __construct(array $columnHeaders, array $rows)
    {
        $this->columnHeaders = $columnHeaders;
        $this->rows          = $rows;
    }

    public static function create(array $columnHeaders, array $rows) : self
    {
        return new self($columnHeaders, $rows);
    }

    public function getColumnHeaders(): array
    {
        return $this->columnHeaders;
    }

    public function getRows(): array
    {
        return $this->rows;
    }
}