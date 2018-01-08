<?php

namespace SimplyCodedSoftware\Messaging;

/**
 * Class Future
 * @package SimplyCodedSoftware\Messaging\Handler\Gateway
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