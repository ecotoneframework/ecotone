<?php


namespace Test\SimplyCodedSoftware\Messaging\Fixture\Handler\Processor\Interceptor;

use SimplyCodedSoftware\Messaging\Annotation\Gateway\Gateway;
use SimplyCodedSoftware\Messaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\Messaging\Transaction\Transactional;

/**
 * Interface TransactionalInterceptorOnGatewayMethodExample
 * @package Fixture\Handler\Processor\Interceptor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageEndpoint()
 */
interface TransactionalInterceptorOnGatewayMethodExample
{
    /**
     * @Gateway(requestChannel="requestChannel")
     * @Transactional({"transactionFactory"})
     */
    public function invoke() : void;
}