<?php

namespace Test\SimplyCodedSoftware\Messaging\Fixture\Service;

/**
 * Class ServiceWithoutAnyMethods
 * @package Test\SimplyCodedSoftware\Messaging\Fixture\Service
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ServiceWithoutAnyMethods
{
    public static function create() : self
    {
        return new self();
    }
}