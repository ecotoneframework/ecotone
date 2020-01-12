<?php

namespace Test\Ecotone\Messaging\Fixture\Behat\ErrorHandling;

use Ecotone\Messaging\Annotation\Gateway;
use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Message;

/**
 * @MessageEndpoint()
 */
interface OrderGateway
{
    /**
     * @Gateway(requestChannel=ErrorConfigurationContext::INPUT_CHANNEL)
     */
    public function order(string $type) : void;

    /**
     * @Gateway(requestChannel="getErrorMessage")
     */
    public function getIncorrectOrder() : ?string;
}