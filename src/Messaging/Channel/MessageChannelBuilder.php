<?php

namespace Ecotone\Messaging\Channel;

use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\MessageChannel;

/**
 * Interface MessageChannelBuilder
 * @package Ecotone\Messaging\Channel
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface MessageChannelBuilder
{
    /**
     * @return string
     */
    public function getMessageChannelName() : string;

    /**
     * @return bool
     */
    public function isPollable() : bool;

    /**
     * @param ReferenceSearchService $referenceSearchService
     * @return MessageChannel
     */
    public function build(ReferenceSearchService $referenceSearchService) : MessageChannel;

    /**
     * @return string[] empty string means no required reference name exists
     */
    public function getRequiredReferenceNames() : array;

    /**
     * @param InterfaceToCallRegistry $interfaceToCallRegistry
     *
     * @return InterfaceToCall[]
     */
    public function resolveRelatedInterfaces(InterfaceToCallRegistry $interfaceToCallRegistry): iterable;
}