<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Config\Annotation\ModuleConfiguration;

use Ecotone\Messaging\Annotation\Endpoint\Delayed;
use Ecotone\Messaging\Annotation\Endpoint\Priority;
use Ecotone\Messaging\Annotation\Endpoint\ExpireAfter;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\EndpointHeaders\EndpointHeadersInterceptor;
use Ecotone\Messaging\MessageHeaders;
use PHPUnit\Framework\TestCase;

/**
 * Class EndpointHeadersInterceptorTest
 * @package Test\Ecotone\Messaging\Unit\Config\Annotation\ModuleConfiguration
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class EndpointHeadersInterceptorTest extends TestCase
{
    public function test_adding_delivery_delay()
    {
        $endpointHeadersInterceptor = new EndpointHeadersInterceptor();
        $annotation = new Delayed(1);

        $this->assertEquals(
            [MessageHeaders::DELIVERY_DELAY => 1],
            $endpointHeadersInterceptor->addMetadata($annotation, null, null)
        );
    }

    public function test_adding_time_to_live()
    {
        $endpointHeadersInterceptor = new EndpointHeadersInterceptor();
        $annotation = new ExpireAfter(1);

        $this->assertEquals(
            [MessageHeaders::TIME_TO_LIVE => 1],
            $endpointHeadersInterceptor->addMetadata(null, $annotation, null)
        );
    }

    public function test_adding_priority()
    {
        $endpointHeadersInterceptor = new EndpointHeadersInterceptor();
        $annotation = new Priority(1);

        $this->assertEquals(
            [MessageHeaders::PRIORITY => 1],
            $endpointHeadersInterceptor->addMetadata(null, null, $annotation)
        );
    }
}