<?php


namespace Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor;

use Ecotone\Messaging\Annotation\MessageGateway;
use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Transaction\Transactional;

/**
 * Interface TransactionalInterceptorOnGatewayExample
 * @package Fixture\Handler\Processor\Interceptor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageEndpoint()
 * @Transactional({"transactionFactory1"})
 */
interface TransactionalInterceptorOnGatewayClassAndMethodExample
{
    /**
     * @MessageGateway(requestChannel="requestChannel")
     * @Transactional({"transactionFactory2"})
     */
    public function invoke() : void;
}