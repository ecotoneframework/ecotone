<?php

namespace Test\SimplyCodedSoftware\Messaging\Fixture\Service;

/**
 * Interface CallableService
 * @package Test\SimplyCodedSoftware\Messaging\Fixture\Service
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface CallableService
{
    /**
     * @return bool
     */
    public function wasCalled() : bool;
}