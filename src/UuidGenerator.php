<?php

namespace Messaging;

use Messaging\Message\Uuid;

/**
 * Interface UuidGenerator
 * @package Messaging
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface UuidGenerator
{
    /**
     * @return Uuid
     */
    public function generateUuid() : Uuid;
}