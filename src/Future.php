<?php

namespace Messaging;

/**
 * Class Future
 * @package Messaging\Handler\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface Future
{
    /**
     * Resolves future by retrieving response
     *
     * @return mixed
     */
    public function resolve();
}