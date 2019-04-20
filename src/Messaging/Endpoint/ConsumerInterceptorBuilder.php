<?php


namespace SimplyCodedSoftware\Messaging\Endpoint;

use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;

/**
 * Class ConsumerExtensionBuilder
 * @package SimplyCodedSoftware\Messaging\Endpoint
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ConsumerInterceptorBuilder
{
    /**
     * @return string
     */
    public function getInterceptorName() : string;

    /**
     * @param ReferenceSearchService $referenceSearchService
     * @return ConsumerInterceptor
     */
    public function build(ReferenceSearchService $referenceSearchService) : ConsumerInterceptor;
}