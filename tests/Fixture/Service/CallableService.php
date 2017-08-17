<?php

namespace Fixture\Service;

/**
 * Interface CallableService
 * @package Fixture\Service
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface CallableService
{
    /**
     * @return bool
     */
    public function wasCalled() : bool;
}