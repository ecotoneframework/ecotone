<?php


namespace Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor;

use Ecotone\Messaging\Annotation\Gateway;
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
     * @Gateway(requestChannel="requestChannel")
     * @Transactional({"transactionFactory2"})
     */
    public function invoke() : void;
}