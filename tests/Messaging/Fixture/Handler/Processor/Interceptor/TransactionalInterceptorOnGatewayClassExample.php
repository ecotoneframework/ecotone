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
 * @Transactional({"transactionFactory"})
 */
interface TransactionalInterceptorOnGatewayClassExample
{
    /**
     * @MessageGateway(requestChannel="requestChannel")
     */
    public function invoke() : void;
}