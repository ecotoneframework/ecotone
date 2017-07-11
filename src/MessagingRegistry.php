<?php

namespace Messaging;

/**
 * Interface MessagingRegistry
 * @package Messaging
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface MessagingRegistry
{
    /**
     * @param string $name
     * @return object
     */
    public function getByName(string $name);
}