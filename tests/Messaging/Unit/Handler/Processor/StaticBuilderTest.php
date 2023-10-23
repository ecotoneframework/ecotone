<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler\Processor;

use Ecotone\Messaging\Config\Container\BoundParameterConverter;
use Ecotone\Messaging\Endpoint\PollingMetadata;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\ValueBuilder;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Test\ComponentTestBuilder;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use Test\Ecotone\Messaging\Fixture\Service\ServiceExpectingOneArgument;

/**
 * Class StaticBuilderTest
 * @package Test\Ecotone\Messaging\Unit\Handler\Processor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 *
 * @internal
 */
class StaticBuilderTest extends TestCase
{
    /**
     * @throws ReflectionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_creating_static_value()
    {
        $interfaceToCall = InterfaceToCall::create(ServiceExpectingOneArgument::class, 'withoutReturnValue');
        $interfaceParameter = $interfaceToCall->getInterfaceParameters()[0];
        $value = PollingMetadata::create('some-id');
        $converter = new BoundParameterConverter(
            new ValueBuilder($interfaceParameter->getName(), $value),
            $interfaceToCall,
        );
        $converter = ComponentTestBuilder::create()->build($converter);

        $this->assertEquals(
            $value,
            $converter->getArgumentFrom(
                MessageBuilder::withPayload('a')->build(),
            )
        );
    }
}
