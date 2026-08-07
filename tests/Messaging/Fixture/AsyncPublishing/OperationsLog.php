<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\AsyncPublishing;

/**
 * licence Apache-2.0
 */
final class OperationsLog
{
    /** @var string[] */
    private array $operations = [];

    public function log(string $operation): void
    {
        $this->operations[] = $operation;
    }

    /**
     * @return string[]
     */
    public function getOperations(): array
    {
        return $this->operations;
    }
}
