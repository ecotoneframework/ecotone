<?php

namespace Test\Ecotone\Messaging\Fixture\Service\ServiceInterface;

/**
 * Interface ServiceInterface
 * @package Test\Ecotone\Messaging\Fixture\Service
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
interface ServiceInterfaceSendOnly
{
    public function sendMail(string $content): void;

    /**
     * @param string $content
     * @param array  $metadata
     */
    public function sendMailWithMetadata(string $content, array $metadata): void;
}
