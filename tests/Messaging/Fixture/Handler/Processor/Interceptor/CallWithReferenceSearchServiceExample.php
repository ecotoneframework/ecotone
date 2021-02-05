<?php

namespace Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor;

use Ecotone\Messaging\Attribute\Interceptor\Around;
use Ecotone\Messaging\Attribute\MessageEndpoint;
use Ecotone\Messaging\Handler\ReferenceSearchService;

/**
 * Class CallWithAnnotationFromMethodInterceptorExample
 * @package Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class CallWithReferenceSearchServiceExample extends BaseInterceptorExample
{
    #[Around]
    public function call(ReferenceSearchService $referenceSearchService) : void
    {

    }
}