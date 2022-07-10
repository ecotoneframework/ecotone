<?php
declare(strict_types=1);


namespace Ecotone\Messaging\Endpoint;

use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference;

/**
 * Class ConsumerLifecycleBuilder
 * @package Ecotone\Messaging\Endpoint
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