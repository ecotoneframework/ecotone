<?php


namespace Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor;

use Ecotone\Messaging\Annotation\MessageGateway;
use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Transaction\Transactional;

/**
 * Interface TransactionalInterceptorOnGatewayMethodExample
 * @package Fixture\Handler\Processor\Interceptor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageEndpoint()
 */
interface TransactionalInterceptorOnGatewayMethodExample
{
    /**
     * @MessageGateway(requestChannel="requestChannel")
     * @Transactional({"transactionFactory"})
     */
    public function invoke() : void;
}