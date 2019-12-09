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

    public function send(string $data, string $sourceMediaType = MediaType::TEXT_PLAIN) : void;

    public function sendWithMetadata(string $data, array $metadata, string $sourceMediaType = MediaType::TEXT_PLAIN) : void;

    public function convertAndSend(object $data) : void;

    public function convertAndSendWithMetadata(object $data, array $metadata) : void;
}