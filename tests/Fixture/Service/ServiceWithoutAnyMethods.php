<?php

namespace Fixture\Service;

/**
 * Class ServiceWithoutAnyMethods
 * @package Fixture\Service
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ServiceWithoutAnyMethods
{
    public static function create() : self
    {
        return new self();
    }
}