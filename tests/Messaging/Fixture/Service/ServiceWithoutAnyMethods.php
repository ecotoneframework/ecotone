<?php

namespace Tests\Ecotone\Messaging\Fixture\Service;

/**
 * Class ServiceWithoutAnyMethods
 * @package Tests\Ecotone\Messaging\Fixture\Service
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ServiceWithoutAnyMethods
{
    public static function create() : self
    {
        return new self();
    }
}