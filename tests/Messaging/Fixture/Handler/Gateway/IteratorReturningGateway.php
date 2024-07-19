<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Handler\Gateway;

use stdClass;

/**
 * licence Apache-2.0
 */
interface IteratorReturningGateway
{
    /**
     * @return iterable<stdClass>
     */
    public function executeIterator(mixed $payload): iterable;

    /**
     * @return iterable<int>
     */
    public function executeIteratorWithScalarType(mixed $payload): iterable;

    public function executeIteratorWithoutType(mixed $payload): iterable;

    /**
     * @return iterable<int, stdClass>
     */
    public function executeWithAdvancedIterator(mixed $payload): iterable;
}
