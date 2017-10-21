<?php

namespace Messaging\Handler;

use Messaging\MessageChannel;
use Messaging\Support\InvalidArgumentException;

/**
 * Interface MessageProducer
 * @package Messaging\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface MessageProducer
{
    /**
     * @param MessageChannel $outputChannel
     * @return void
     */
    public function setOutputChannel(MessageChannel $outputChannel) : void;

    /**
     * @return bool
     */
    public function hasOutputChannel() : bool;

    /**
     * @return MessageChannel
     * @throws InvalidArgumentException when retrieving not existing output channel
     */
    public function getOutputChannel() : MessageChannel;

    /**
     * @return bool
     */
    public function isReplyRequired() : bool;

    /**
     * Marks producer to require reply message
     */
    public function requireReply() : void;
}