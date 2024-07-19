<?php

namespace Test\Ecotone\Messaging\Fixture\Handler\Gateway;

use Ecotone\Messaging\Handler\NonProxyGateway;

/**
 * licence Apache-2.0
 */
class GatewayExecuteClass implements NonProxyGateway
{
    private $returnData;

    public function __construct($returnData)
    {
        $this->returnData = $returnData;
    }

    public function execute(array $methodArgumentValues)
    {
        return $this->returnData;
    }
}
