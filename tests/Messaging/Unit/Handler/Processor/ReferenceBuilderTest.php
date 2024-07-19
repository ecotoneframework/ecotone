<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler\Processor;

use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\ReferenceBuilder;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Test\ComponentTestBuilder;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use stdClass;
use Test\Ecotone\Messaging\Fixture\Service\ServiceExpectingOneArgument;

/**
 * Class ReferenceBuilderTest
 * @package Test\Ecotone\Messaging\Unit\Handler\Processor
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 *
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
class ReferenceBuilderTest extends TestCase
{
    /**
     * @throws ReflectionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_creating_reference_converter()
    {
        $messaging = ComponentTestBuilder::create()
            ->withReference($referenceName = 'refName', $value = new stdClass())
            ->withMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference(ServiceExpectingOneArgument::create(), 'withUnionParameter')
                    ->withInputChannelName($inputChannel = 'inputChannel')
                    ->withMethodParameterConverters([
                        ReferenceBuilder::create('value', $referenceName),
                    ])
            )
            ->build();

        $this->assertEquals(
            $value,
            $messaging->sendDirectToChannel($inputChannel)
        );
    }

    /**
     * @throws ReflectionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_creating_with_dynamic_reference_resolution()
    {
        $messaging = ComponentTestBuilder::create()
            ->withReference(stdClass::class, $value = new stdClass())
            ->withMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference(ServiceExpectingOneArgument::create(), 'withUnionParameter')
                    ->withInputChannelName($inputChannel = 'inputChannel')
                    ->withMethodParameterConverters([
                        ReferenceBuilder::create('value', stdClass::class),
                    ])
            )
            ->build();

        $this->assertEquals(
            $value,
            $messaging->sendDirectToChannel($inputChannel)
        );
    }

    /**
     * @throws ReflectionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_creating_reference_converter_with_expression()
    {
        $value = new stdClass();
        $value->name = 'someName';

        $messaging = ComponentTestBuilder::create()
            ->withReference($referenceName = 'refName', $value)
            ->withMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference(ServiceExpectingOneArgument::create(), 'withUnionParameter')
                    ->withInputChannelName($inputChannel = 'inputChannel')
                    ->withMethodParameterConverters([
                        ReferenceBuilder::create('value', $referenceName, 'service.name'),
                    ])
            )
            ->build();

        $this->assertEquals(
            'someName',
            $messaging->sendDirectToChannel($inputChannel)
        );
    }
}
