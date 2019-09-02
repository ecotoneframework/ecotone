<?php
declare(strict_types=1);

namespace Ecotone\Messaging;

use Ecotone\Messaging\Conversion\MediaType;

/**
 * Interface AmqpPublisher
 * @package Ecotone\Amqp
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface Publisher
{
    /**
     * @param string $data
     * @param string $sourceMediaType
     */
    public function send(string $data, string $sourceMediaType = MediaType::TEXT_PLAIN) : void;

    /**
     * @param string $data
     * @param string $sourceMediaType
     * @param string[] $metadata
     */
    public function sendWithMetadata(string $data, array $metadata, string $sourceMediaType = MediaType::TEXT_PLAIN) : void;

    /**
     * @param $data
     * @return void
     */
    public function convertAndSend(object $data) : void;

    /**
     * @param $data
     * @param string[] $metadata
     * @return void
     */
    public function convertAndSendWithMetadata(object $data, array $metadata) : void;
}