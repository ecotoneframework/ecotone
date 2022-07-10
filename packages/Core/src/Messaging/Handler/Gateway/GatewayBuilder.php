<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Gateway;

use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\InterceptedEndpoint;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\NonProxyGateway;
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
    public function addBeforeInterceptor(MethodInterceptor $methodInterceptor): self;

    /**
     * @param bool $withLazyBuild
     * @return $this
     */
    public function withLazyBuild(bool $withLazyBuild): self;

    /**
     * @param MethodInterceptor $methodInterceptor
     * @return $this
     */
    public function addAfterInterceptor(MethodInterceptor $methodInterceptor): self;

    /**
     * @param string[] $messageConverterReferenceNames
     * @return $this
     */
    public function withMessageConverters(array $messageConverterReferenceNames): self;

    /**
     * @param ReferenceSearchService $referenceSearchService
     * @param ChannelResolver $channelResolver
     */
    public function build(ReferenceSearchService $referenceSearchService, ChannelResolver $channelResolver): object;

    public function buildWithoutProxyObject(ReferenceSearchService $referenceSearchService, ChannelResolver $channelResolver) : NonProxyGateway;
}