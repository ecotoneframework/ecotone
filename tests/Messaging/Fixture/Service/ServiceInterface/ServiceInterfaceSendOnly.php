<?php

namespace Ecotone\Tests\Messaging\Fixture\Service\ServiceInterface;

/**
 * Interface ServiceInterface
 * @package Ecotone\Tests\Messaging\Fixture\Service
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ServiceInterfaceSendOnly
{
    public function sendMail(string $content) : void;

    /**
     * @param string $content
     * @param array  $metadata
     */
    public function sendMailWithMetadata(string $content, array $metadata) : void;
}