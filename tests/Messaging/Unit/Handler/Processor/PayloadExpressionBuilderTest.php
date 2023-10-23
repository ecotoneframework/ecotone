<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler\Processor;

use Ecotone\Messaging\Config\Container\BoundParameterConverter;
use Ecotone\Messaging\Handler\InterfaceParameter;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\PayloadExpressionBuilder;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Test\ComponentTestBuilder;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use Test\Ecotone\Messaging\Fixture\Service\CalculatingService;
use Test\Ecotone\Messaging\Fixture\Service\CallableService;

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
        $converter = PayloadExpressionBuilder::create('x', 'value ~ 1');
        $converter = ComponentTestBuilder::create()
            ->build(new BoundParameterConverter(
                $converter,
                InterfaceToCall::create(CallableService::class, 'wasCalled'),
                InterfaceParameter::createNullable('x', TypeDescriptor::createWithDocBlock('string', ''))
            ));

        $this->assertEquals(
            '1001',
            $converter->getArgumentFrom(
                MessageBuilder::withPayload('100')
                    ->build(),
            )
        );
    }

    /**
     * @throws \Ecotone\Messaging\Handler\TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_using_reference_service_in_expression()
    {
        $converter = PayloadExpressionBuilder::create('x', "reference('calculatingService').sum(value)");

        $converter = ComponentTestBuilder::create()
            ->withReference('calculatingService', CalculatingService::create(1))
            ->build(new BoundParameterConverter(
                $converter,
                InterfaceToCall::create(CallableService::class, 'wasCalled'),
                InterfaceParameter::createNullable('x', TypeDescriptor::createWithDocBlock('string', ''))
            ));


        $this->assertEquals(
            101,
            $converter->getArgumentFrom(
                MessageBuilder::withPayload(100)
                    ->build(),
            )
        );
    }
}
