<?php


namespace Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor;

use Ecotone\Messaging\Annotation\MessageGateway;
use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Transaction\Transactional;

/**
 * @Transactional({"transactionFactory"})
 */
interface TransactionalInterceptorOnGatewayClassExample
{
    /**
     * @MessageGateway(requestChannel="requestChannel")
     */
    public function invoke() : void;
}