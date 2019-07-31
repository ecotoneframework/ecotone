<?php

namespace Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor;

use Ecotone\Messaging\Transaction\Transactional;

/**
 * Class TransactionalInterceptorExample
 * @package Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Transactional({"reference1"})
 */
class TransactionalInterceptorExample
{
    /**
     * @Transactional(factoryReferenceNameList={"reference2"})
     */
    public function doAction() : void
    {

    }
}