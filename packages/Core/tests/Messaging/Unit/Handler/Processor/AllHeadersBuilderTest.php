<?php


namespace Test\Ecotone\Messaging\Unit\Handler\Processor;

use PHPUnit\Framework\TestCase;
use Ecotone\Messaging\Handler\InMemoryReferenceSearchService;
use Ecotone\Messaging\Handler\InterfaceParameter;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\AllHeadersBuilder;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Support\MessageBuilder;
use Test\Ecotone\Messaging\Fixture\Service\CallableService;

/**
 * Class AllHeadersBuilderTest
 * @package Test\Ecotone\Messaging\Unit\Handler\Processor
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AllHeadersBuilderTest extends TestCase
{
    public function test_retrieving_all_headers()
    {
        $result = AllHeadersBuilder::createWith("some")->build(InMemoryReferenceSearchService::createEmpty())->getArgumentFrom(
            InterfaceToCall::create(CallableService::class, "wasCalled"),
            InterfaceParameter::createNullable("some", TypeDescriptor::createStringType()),
            MessageBuilder::withPayload("some")
                ->setHeader("someId", 123)
                ->build(),
            []
        );
        unset($result[MessageHeaders::MESSAGE_ID]);
        unset($result[MessageHeaders::TIMESTAMP]);

        $this->assertEquals(
            [
                "someId" => 123
            ],
            $result
        );
    }
}