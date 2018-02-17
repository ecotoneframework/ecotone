<?php

namespace Fixture\Service;

/**
 * Class StaticallyCalledService
 * @package Fixture\Service
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class StaticallyCalledService
{
    private function __construct()
    {
    }

    /**
     * @param string $something
     * @return string
     */
    public static function run(string $something) : string
    {
        return $something;
    }
}