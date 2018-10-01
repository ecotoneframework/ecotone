<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Handler\Processor;
use Builder\Handler\InterfaceParameterTestCaseBuilder;
use PHPUnit\Framework\TestCase;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InterfaceParameter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\ReferenceBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Support\MessageBuilder;

/**
 * Class ReferenceBuilderTest
 * @package Test\SimplyCodedSoftware\IntegrationMessaging\Handler\Processor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ReferenceBuilderTest extends TestCase
{
    /**
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_creating_reference_converter()
    {
        $referenceName = "refName";
        $value = new \stdClass();
        $converter = ReferenceBuilder::create("paramName", $referenceName)
            ->build(InMemoryReferenceSearchService::createWith([
                $referenceName => $value
            ]));

        $this->assertEquals(
            $value,
            $converter->getArgumentFrom(
                InterfaceParameter::create("x", \stdClass::class, true, ""),
                MessageBuilder::withPayload("paramName")->build()
            )
        );
    }

    /**
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_creating_with_dynamic_reference_resolution()
    {
        $value = new \stdClass();
        $converter = ReferenceBuilder::createWithDynamicResolve("param")
            ->build(InMemoryReferenceSearchService::createWith([
                \stdClass::class => $value
            ]));

        $this->assertEquals(
            $value,
            $converter->getArgumentFrom(
                InterfaceParameter::create("x", \stdClass::class, true, ""),
                MessageBuilder::withPayload("paramName")->build()
            )
        );
    }
}