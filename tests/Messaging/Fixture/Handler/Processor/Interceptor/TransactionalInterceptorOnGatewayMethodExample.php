<?php


namespace Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor;

use Ecotone\Messaging\Annotation\MessageGateway;
use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Transaction\Transactional;

interface TransactionalInterceptorOnGatewayMethodExample
{
    #[Transactional(["transactionFactory"])]
    #[MessageGateway("requestChannel")]
    public function invoke() : void;
}