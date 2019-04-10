<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler\Gateway;

use SimplyCodedSoftware\Messaging\Handler\ChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\MethodInterceptor;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;

/**
 * Interface Gateway
 * @package SimplyCodedSoftware\Messaging\Handler\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface GatewayBuilder
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

//    /**
//     * @param AroundInterceptorReference $aroundInterceptorReference
//     * @return $this
//     */
//    public function addAroundInterceptor(AroundInterceptorReference $aroundInterceptorReference);
//
//    /**
//     * @param MethodInterceptor $methodInterceptor
//     * @return $this
//     */
//    public function addBeforeInterceptor(MethodInterceptor $methodInterceptor);
//
//    /**
//     * @param MethodInterceptor $methodInterceptor
//     * @return $this
//     */
//    public function addAfterInterceptor(MethodInterceptor $methodInterceptor);
//
//    /**
//     * @param object[] $endpointAnnotations
//     * @return static
//     */
//    public function withEndpointAnnotations(iterable $endpointAnnotations);
//
//    /**
//     * @return object[]
//     */
//    public function getEndpointAnnotations(): iterable;

    /**
     * @param ReferenceSearchService $referenceSearchService
     * @param ChannelResolver $channelResolver
     * @return object
     */
    public function build(ReferenceSearchService $referenceSearchService, ChannelResolver $channelResolver);
}