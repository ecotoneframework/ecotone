<?php

namespace Ecotone\Tests\Messaging\Fixture\Service;

/**
 * Class ServiceWithoutAnyMethods
 * @package Ecotone\Tests\Messaging\Fixture\Service
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ServiceWithoutAnyMethods
{
    public static function create() : self
    {
        return new self();
    }
}