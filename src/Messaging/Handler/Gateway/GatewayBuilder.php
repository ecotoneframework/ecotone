<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Gateway;

use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\InterceptedEndpoint;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInterceptor;
use Ecotone\Messaging\Handler\ReferenceSearchService;

/**
 * Interface Gateway
 * @package Ecotone\Messaging\Handler\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface GatewayBuilder extends InterceptedEndpoint
{
    /**
     * Name to be registered under
     *
     * @return string
     */
    public function getReferenceName() : string;

    /**
     * @return string[]
     */
    public function getRequiredReferences() : array;

    /**
     * @return string
     */
    public function getInterfaceName() : string;

    /**
     * @return string
     */
    public function getRelatedMethodName() : string;

    /**
     * @param MethodInterceptor $methodInterceptor
     * @return $this
     */
    public function addBeforeInterceptor(MethodInterceptor $methodInterceptor);

    /**
     * @param MethodInterceptor $methodInterceptor
     * @return $this
     */
    public function addAfterInterceptor(MethodInterceptor $methodInterceptor);

    /**
     * @param ReferenceSearchService $referenceSearchService
     * @param ChannelResolver $channelResolver
     * @return object
     */
    public function build(ReferenceSearchService $referenceSearchService, ChannelResolver $channelResolver);
}