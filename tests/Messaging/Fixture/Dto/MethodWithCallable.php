<?php

namespace Test\Ecotone\Messaging\Fixture\Dto;

final class MethodWithCallable
{
    public function execute(callable $closure): callable
    {
        return function (){};
    }
}