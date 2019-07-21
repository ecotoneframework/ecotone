<?php

namespace SimplyCodedSoftware\Messaging\Channel;

use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;

/**
 * Interface ChannelInterceptorBuilder
 * @package SimplyCodedSoftware\Messaging\Channel
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
     * @return int
     */
    public function getPrecedence() : int;

    /**
     * @param ReferenceSearchService $referenceSearchService
     * @return ChannelInterceptor
     */
    public function build(ReferenceSearchService $referenceSearchService) : ChannelInterceptor;
}