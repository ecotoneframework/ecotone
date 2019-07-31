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
 * @Transactional({"transactionFactory"})
 */
interface TransactionalInterceptorOnGatewayClassExample
{
    /**
     * @Gateway(requestChannel="requestChannel")
     */
    public function invoke() : void;
}