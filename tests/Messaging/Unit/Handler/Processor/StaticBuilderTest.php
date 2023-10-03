<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler\Processor;

use Ecotone\Messaging\Handler\InMemoryReferenceSearchService;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\ValueBuilder;
use Ecotone\Messaging\Support\MessageBuilder;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use stdClass;
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
        $value = new stdClass();
        $converter = new ValueBuilder($interfaceParameter->getName(), $value);
        $converter = $converter->build(InMemoryReferenceSearchService::createEmpty(), $interfaceToCall, $interfaceParameter);

        $this->assertEquals(
            $value,
            $converter->getArgumentFrom(
                MessageBuilder::withPayload('a')->build(),
            )
        );
    }
}
