<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler\Processor;

use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\PayloadBuilder;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Test\ComponentTestBuilder;
use ReflectionException;
use Test\Ecotone\Messaging\Fixture\Service\ServiceExpectingOneArgument;
use Test\Ecotone\Messaging\Unit\MessagingTestCase;

/**
 * Class PayloadBuilder
 * @package Test\Ecotone\Messaging\Fixture\Handler\Processor
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 *
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
class PayloadBuilderTest extends MessagingTestCase
{
    /**
     * @throws ReflectionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_creating_payload_converter()
    {
        $messaging = ComponentTestBuilder::create()
            ->withMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference(ServiceExpectingOneArgument::create(), 'withReturnMixed')
                    ->withInputChannelName($inputChannel = 'inputChannel')
                    ->withMethodParameterConverters([
                        PayloadBuilder::create('value'),
                    ])
            )
            ->build();

        $this->assertEquals(
            100,
            $messaging->sendDirectToChannel($inputChannel, 100)
        );
    }
}
