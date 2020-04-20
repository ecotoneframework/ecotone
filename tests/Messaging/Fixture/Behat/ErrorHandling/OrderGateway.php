<?php

namespace Test\Ecotone\Messaging\Fixture\Behat\ErrorHandling;

use Ecotone\Messaging\Annotation\MessageGateway;
use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Message;

/**
 * @MessageEndpoint()
 */
interface OrderGateway
{
    /**
     * @MessageGateway(requestChannel=ErrorConfigurationContext::INPUT_CHANNEL)
     */
    public function order(string $type) : void;

    /**
     * @MessageGateway(requestChannel="getErrorMessage")
     */
    public function getIncorrectOrder() : ?string;
}