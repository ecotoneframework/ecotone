<?php
declare(strict_types=1);


namespace SimplyCodedSoftware\Messaging\Endpoint;

use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference;

/**
 * Class ConsumerLifecycleBuilder
 * @package SimplyCodedSoftware\Messaging\Endpoint
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ConsumerLifecycleBuilder
{
    /**
     * @param AroundInterceptorReference $aroundInterceptorReference
     *
     * @return static
     */
    public function addAroundInterceptor(AroundInterceptorReference $aroundInterceptorReference);
}