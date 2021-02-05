<?php

namespace Test\Ecotone\Messaging\Fixture\Behat\ErrorHandling\DeadLetter;

use Ecotone\Messaging\Attribute\MessageEndpoint;
use Ecotone\Messaging\Attribute\MessageGateway;

interface OrderGateway
{
    #[MessageGateway(ErrorConfigurationContext::INPUT_CHANNEL)]
    public function order(string $type) : void;

    #[MessageGateway("getErrorMessage")]
    public function getIncorrectOrder() : ?string;
}