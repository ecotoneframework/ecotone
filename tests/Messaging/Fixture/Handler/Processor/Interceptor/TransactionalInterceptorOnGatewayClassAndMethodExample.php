<?php

namespace Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor;

use Ecotone\Messaging\Attribute\MessageGateway;
use Ecotone\Messaging\Transaction\Transactional;

#[Transactional(['transactionFactory1'])]
/**
 * licence Apache-2.0
 */
interface TransactionalInterceptorOnGatewayClassAndMethodExample
{
    #[Transactional(['transactionFactory2'])]
    #[MessageGateway('requestChannel')]
    public function invoke(): void;
}
