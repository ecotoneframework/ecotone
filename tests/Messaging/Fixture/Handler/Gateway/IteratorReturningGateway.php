<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Handler\Gateway;

use stdClass;

interface IteratorReturningGateway
{
    /**
     * @return iterable<stdClass>
     */
    public function executeIterator(): iterable;

    /**
     * @return iterable<int>
     */
    public function executeIteratorWithScalarType(): iterable;

    public function executeIteratorWithoutType(): iterable;

    /**
     * @return iterable<int, stdClass>
     */
    public function executeWithAdvancedIterator(): iterable;
}
