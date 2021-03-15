<?php

namespace Ecotone\Messaging\Channel;

use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\ReferenceSearchService;

/**
 * Interface ChannelInterceptorBuilder
 * @package Ecotone\Messaging\Channel
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ChannelInterceptorBuilder
{
    /**
     * @return string
     */
    public function relatedChannelName() : string;

    /**
     * @return string[] empty string means no required reference name exists
     */
    public function getRequiredReferenceNames() : array;

    /**
     * It returns, internal reference objects that will be called during handling method
     *
     * @param InterfaceToCallRegistry $interfaceToCallRegistry
     * @return InterfaceToCall[]
     */
    public function resolveRelatedInterfaces(InterfaceToCallRegistry $interfaceToCallRegistry) : iterable;

    /**
     * @return int
     */
    public function getPrecedence() : int;

    /**
     * @param ReferenceSearchService $referenceSearchService
     * @return ChannelInterceptor
     */
    public function build(ReferenceSearchService $referenceSearchService) : ChannelInterceptor;
}