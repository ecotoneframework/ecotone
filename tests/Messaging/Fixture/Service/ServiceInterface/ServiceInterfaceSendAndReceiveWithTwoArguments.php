<?php

namespace Test\Ecotone\Messaging\Fixture\Service\ServiceInterface;

/**
 * Interface ServiceInterfaceSendAndReceiveWithTwoArguments
 * @package Test\Ecotone\Messaging\Fixture\Service\ServiceInterface
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
interface ServiceInterfaceSendAndReceiveWithTwoArguments
{
    public function sendMail(string $personId, string $content): string;
}
