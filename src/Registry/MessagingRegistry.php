<?php

namespace Messaging\Registry;

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