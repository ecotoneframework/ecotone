<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Handler\Processor;
use PHPUnit\Framework\TestCase;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\HeaderBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Support\MessageBuilder;

/**
 * Class HeaderBuilderTest
 * @package Test\SimplyCodedSoftware\IntegrationMessaging\Handler\Processor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class HeaderBuilderTest extends TestCase
{
    /**
     * @throws \SimplyCodedSoftware\IntegrationMessaging\InvalidMessageHeaderException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_creating_header_converter()
    {
        $converter = HeaderBuilder::create("some", "token");
        $converter = $converter->build(InMemoryReferenceSearchService::createEmpty());

        $this->assertEquals(
            123,
            $converter->getArgumentFrom(MessageBuilder::withPayload("a")->setHeader("token", 123)->build())
        );
    }

    /**
     * @throws \SimplyCodedSoftware\IntegrationMessaging\InvalidMessageHeaderException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_creating_optional_header_converter()
    {
        $converter = HeaderBuilder::createOptional("some", "token");
        $converter = $converter->build(InMemoryReferenceSearchService::createEmpty());

        $this->assertEquals(
            null,
            $converter->getArgumentFrom(MessageBuilder::withPayload("a")->build())
        );
    }
}