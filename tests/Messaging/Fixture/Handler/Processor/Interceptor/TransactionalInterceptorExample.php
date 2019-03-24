<?php

namespace Test\SimplyCodedSoftware\Messaging\Fixture\Handler\Processor\Interceptor;

use SimplyCodedSoftware\Messaging\Transaction\Transactional;

/**
 * Class TransactionalInterceptorExample
 * @package Test\SimplyCodedSoftware\Messaging\Fixture\Handler\Processor\Interceptor
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