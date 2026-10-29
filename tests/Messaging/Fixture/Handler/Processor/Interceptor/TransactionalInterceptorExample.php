<?php

namespace Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor;

use Ecotone\Messaging\Transaction\Transactional;

#[Transactional(['reference1'])]
/**
 * licence Apache-2.0
 */
class TransactionalInterceptorExample
{
    #[Transactional(['reference2'])]
    public function doAction(): void
    {
    }
}
