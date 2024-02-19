<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler\Processor;

use Ecotone\Messaging\Config\Container\BoundParameterConverter;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\ReferenceBuilder;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Test\ComponentTestBuilder;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use stdClass;
use Test\Ecotone\Messaging\Fixture\Service\ServiceExpectingOneArgument;

/**
 * Class ReferenceBuilderTest
 * @package Test\Ecotone\Messaging\Unit\Handler\Processor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 *
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
        $referenceName = 'refName';
        $interfaceToCall = InterfaceToCall::create(ServiceExpectingOneArgument::class, 'withUnionParameter');
        $interfaceParameter = $interfaceToCall->getInterfaceParameters()[0];
        $value = new stdClass();
        $converter = ComponentTestBuilder::create()
            ->withReference($referenceName, $value)
            ->build(new BoundParameterConverter(
                ReferenceBuilder::create($interfaceParameter->getName(), $referenceName),
                $interfaceToCall,
                $interfaceParameter
            ));

        $this->assertEquals(
            $value,
            $converter->getArgumentFrom(
                MessageBuilder::withPayload('paramName')->build(),
            )
        );
    }

    /**
     * @throws ReflectionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_creating_with_dynamic_reference_resolution()
    {
        $interfaceToCall = InterfaceToCall::create(ServiceExpectingOneArgument::class, 'withUnionParameter');
        $interfaceParameter = $interfaceToCall->getInterfaceParameters()[0];
        $value = new stdClass();
        $converter = ComponentTestBuilder::create()
            ->withReference(stdClass::class, $value)
            ->build(new BoundParameterConverter(
                ReferenceBuilder::create($interfaceParameter->getName(), stdClass::class),
                $interfaceToCall,
                $interfaceParameter
            ));

        $this->assertEquals(
            $value,
            $converter->getArgumentFrom(
                MessageBuilder::withPayload('paramName')->build(),
            )
        );
    }

    /**
     * @throws ReflectionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_creating_reference_converter_with_expression()
    {
        $referenceName = 'refName';
        $interfaceToCall = InterfaceToCall::create(ServiceExpectingOneArgument::class, 'withUnionParameter');
        $interfaceParameter = $interfaceToCall->getInterfaceParameters()[0];
        $value = new stdClass();
        $value->name = 'someName';

        $converter = ComponentTestBuilder::create()
            ->withReference($referenceName, $value)
            ->build(new BoundParameterConverter(
                ReferenceBuilder::create($interfaceParameter->getName(), $referenceName, 'service.name'),
                $interfaceToCall,
                $interfaceParameter
            ));

        $this->assertEquals(
            'someName',
            $converter->getArgumentFrom(
                MessageBuilder::withPayload('paramName')->build(),
            )
        );
    }
}
