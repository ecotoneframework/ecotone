<?php

namespace Test\SimplyCodedSoftware\Messaging\Fixture\Handler\Processor\Interceptor;

use SimplyCodedSoftware\Messaging\Annotation\Interceptor\Around;
use SimplyCodedSoftware\Messaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;

/**
 * Class CallWithAnnotationFromMethodInterceptorExample
 * @package Test\SimplyCodedSoftware\Messaging\Fixture\Handler\Processor\Interceptor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class CallWithReferenceSearchServiceExample extends BaseInterceptorExample
{
    /**
     * @param ReferenceSearchService $referenceSearchService
     * @Around()
     */
    public function call(ReferenceSearchService $referenceSearchService) : void
    {

    }
}