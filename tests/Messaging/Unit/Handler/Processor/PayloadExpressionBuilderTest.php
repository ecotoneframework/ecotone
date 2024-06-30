<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler\Processor;

use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\PayloadExpressionBuilder;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
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
class PayloadExpressionBuilderTest extends TestCase
{
    /**
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws ReflectionException
     * @throws \Ecotone\Messaging\Handler\TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     * @throws \Ecotone\Messaging\Support\InvalidArgumentException
     */
    public function test_creating_payload_expression()
    {
        $messaging = ComponentTestBuilder::create()
            ->withMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference(ServiceExpectingOneArgument::create(), 'withReturnMixed')
                    ->withInputChannelName($inputChannel = 'inputChannel')
                    ->withMethodParameterConverters([
                        PayloadExpressionBuilder::create('value', 'value ~ 1'),
                    ])
            )
            ->build();

        $this->assertEquals(
            '1001',
            $messaging->sendDirectToChannel($inputChannel, 100)
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
                        PayloadExpressionBuilder::create('value', "reference('calculatingService').sum(value)"),
                    ])
            )
            ->build();

        $this->assertEquals(
            101,
            $messaging->sendDirectToChannel($inputChannel, 100)
        );
    }
}
