<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Endpoint;

use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorBuilder;

/**
 * Class ConsumerLifecycleBuilder
 * @package Ecotone\Messaging\Endpoint
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
interface ConsumerLifecycleBuilder
{
    /**
     * @param AroundInterceptorBuilder $aroundInterceptorReference
     *
     * @return static
     */
    public function addAroundInterceptor(AroundInterceptorBuilder $aroundInterceptorReference);
}
