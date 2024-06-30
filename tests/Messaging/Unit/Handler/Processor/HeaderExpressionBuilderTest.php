<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler\Processor;

use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\HeaderExpressionBuilder;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Test\ComponentTestBuilder;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use Test\Ecotone\Messaging\Fixture\Service\CalculatingService;
use Test\Ecotone\Messaging\Fixture\Service\ServiceExpectingOneArgument;

/**
 * Class ExpressionBuilderTest
 * @package Test\Ecotone\Messaging\Unit\Handler\Processor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 *
 * @internal
 */
class HeaderExpressionBuilderTest extends TestCase
{
    /**
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws ReflectionException
     * @throws \Ecotone\Messaging\Handler\TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     * @throws InvalidArgumentException
     */
    public function test_creating_payload_expression()
    {
        $messaging = ComponentTestBuilder::create()
            ->withMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference(ServiceExpectingOneArgument::create(), 'withReturnMixed')
                    ->withInputChannelName($inputChannel = 'inputChannel')
                    ->withMethodParameterConverters([
                        HeaderExpressionBuilder::create('value', 'token', 'value ~ 1', true),
                    ])
            )
            ->build();

        $this->assertEquals(
            '1001',
            $messaging->sendDirectToChannel($inputChannel, metadata: ['token' => 100])
        );
    }

    /**
     * @throws \Ecotone\Messaging\Handler\TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_using_reference_service_in_expression()
    {
        $messaging = ComponentTestBuilder::create()
            ->withReference('calculatingService', CalculatingService::create(1))
            ->withMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference(ServiceExpectingOneArgument::create(), 'withReturnMixed')
                    ->withInputChannelName($inputChannel = 'inputChannel')
                    ->withMethodParameterConverters([
                        HeaderExpressionBuilder::create('value', 'number', "reference('calculatingService').sum(value)", true),
                    ])
            )
            ->build();

        $this->assertEquals(
            101,
            $messaging->sendDirectToChannel($inputChannel, metadata: ['number' => 100])
        );
    }

    public function test_throwing_exception_if_header_does_not_exists()
    {
        $messaging = ComponentTestBuilder::create()
            ->withMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference(ServiceExpectingOneArgument::create(), 'withReturnMixed')
                    ->withInputChannelName($inputChannel = 'inputChannel')
                    ->withMethodParameterConverters([
                        HeaderExpressionBuilder::create('value', 'token', 'value ~ 1', true),
                    ])
            )
            ->build();

        $this->expectException(InvalidArgumentException::class);

        $this->assertEquals(
            '1001',
            $messaging->sendDirectToChannel($inputChannel, metadata: [])
        );
    }

    public function test_not_throwing_exception_if_header_does_not_exists_and_is_no_required()
    {
        $messaging = ComponentTestBuilder::create()
            ->withMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference(ServiceExpectingOneArgument::create(), 'withReturnMixed')
                    ->withInputChannelName($inputChannel = 'inputChannel')
                    ->withMethodParameterConverters([
                        HeaderExpressionBuilder::create('value', 'token', 'value', false),
                    ])
            )
            ->build();

        $this->assertNull(
            $messaging->sendDirectToChannel($inputChannel, metadata: [])
        );
    }
}
