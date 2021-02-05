<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Handler\Gateway;

use Ecotone\Messaging\Attribute\MessageGateway;
use Ecotone\Messaging\Attribute\MessageEndpoint;

interface MultipleMethodsGatewayExample
{
    #[MessageGateway("channel1")]
    public function execute1($data) : void;

    #[MessageGateway("channel2")]
    public function execute2($data) : void;
}