<?php

namespace Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor;

use Ecotone\Messaging\Attribute\MessageGateway;
use Ecotone\Messaging\Transaction\Transactional;

/**
 * licence Apache-2.0
 */
interface TransactionalInterceptorOnGatewayMethodExample
{
    #[Transactional(['transactionFactory'])]
    #[MessageGateway('requestChannel')]
    public function invoke(): void;
}
