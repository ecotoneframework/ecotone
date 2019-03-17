<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Transaction;

use SimplyCodedSoftware\Messaging\Annotation\Interceptor\Around;
use SimplyCodedSoftware\Messaging\Annotation\Interceptor\MethodInterceptor;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;

/**
 * Class TransactionInterceptor
 * @package SimplyCodedSoftware\Messaging\Transaction
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @MethodInterceptor()
 */
class TransactionInterceptor
{


    /**
     * @Around()
     * @param MethodInvocation $methodInvocation
     * @param Transactional $transactional
     */
    public function transactional(MethodInvocation $methodInvocation, Transactional $transactional) : void
    {
//        $methodInvocation->
    }
}