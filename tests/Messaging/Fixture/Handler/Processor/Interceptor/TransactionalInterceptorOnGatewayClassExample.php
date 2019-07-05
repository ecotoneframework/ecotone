<?php


namespace Test\SimplyCodedSoftware\Messaging\Fixture\Handler\Processor\Interceptor;

use SimplyCodedSoftware\Messaging\Annotation\Gateway\Gateway;
use SimplyCodedSoftware\Messaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\Messaging\Transaction\Transactional;

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