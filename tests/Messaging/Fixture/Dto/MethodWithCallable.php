<?php

namespace Test\Ecotone\Messaging\Fixture\Dto;

/**
 * licence Apache-2.0
 */
final class MethodWithCallable
{
    public function execute(callable $closure): callable
    {
        return function () {
        };
    }
}
