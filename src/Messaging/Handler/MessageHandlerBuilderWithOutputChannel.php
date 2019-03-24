<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler;

use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference;

/**
 * Interface MessageHandlerBuilderWithOutputChannel
 * @package SimplyCodedSoftware\Messaging\Handler
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface MessageHandlerBuilderWithOutputChannel extends MessageHandlerBuilder
{
    /**
     * @param string $messageChannelName
     *
     * @return static
     */
    public function withOutputMessageChannel(string $messageChannelName);

    /**
     * @return string
     */
    public function getOutputMessageChannelName() : string;

    /**
     * @param AroundInterceptorReference $aroundInterceptorReference
     * @return self
     */
    public function addAroundInterceptor(AroundInterceptorReference $aroundInterceptorReference);

    /**
     * @param InterfaceToCallRegistry $interfaceToCallRegistry
     * @return InterfaceToCall
     */
    public function getInterceptedInterface(InterfaceToCallRegistry $interfaceToCallRegistry) : InterfaceToCall;

    /**
     * @param iterable $endpointAnnotations
     * @return static
     */
    public function withEndpointAnnotations(iterable $endpointAnnotations);

    /**
     * @return object[]
     */
    public function getEndpointAnnotations(): array;

    /**
     * @return string[]
     */
    public function getRequiredInterceptorReferenceNames() : iterable;
}