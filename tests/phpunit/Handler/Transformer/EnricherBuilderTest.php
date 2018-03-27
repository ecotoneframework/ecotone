<?php

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Handler\Transformer;

use SimplyCodedSoftware\IntegrationMessaging\Channel\QueueChannel;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationException;
use SimplyCodedSoftware\IntegrationMessaging\Config\InMemoryChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\EnricherBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\HeaderSetter\StaticHeaderSetterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\PropertySetter\StaticSetterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\Support\MessageBuilder;
use Test\SimplyCodedSoftware\IntegrationMessaging\MessagingTest;

/**
 * Class PayloadEnricherBuilderTest
 * @package Test\SimplyCodedSoftware\IntegrationMessaging\Handler\Transformer
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class EnricherBuilderTest extends MessagingTest
{
    /**
     * @throws ConfigurationException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_throwing_exception_if_no_property_or_header_setters_configured()
    {
        $this->expectException(ConfigurationException::class);

        $enricher = EnricherBuilder::create("some");

        $enricher->build(InMemoryChannelResolver::createEmpty(), InMemoryReferenceSearchService::createEmpty());
    }

    /**
     * @throws ConfigurationException
     * @throws \Exception
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_enriching_array_with_multiple_static_properties()
    {
        $outputChannel = QueueChannel::create();
        $enricher           =
        EnricherBuilder::create("some")
            ->withPropertySetters([
                StaticSetterBuilder::createWith("token", "123"),
                StaticSetterBuilder::createWith("password", "secret")
            ])
            ->build(
                InMemoryChannelResolver::createEmpty(),
                InMemoryReferenceSearchService::createEmpty()
            );

        $payload = [];
        $enricher->handle($this->createMessageWith($payload, $outputChannel)->build());

        $this->assertEquals(
            [
                "token" => 123,
                "password" => "secret"
            ],
            $outputChannel->receive()->getPayload()
        );
    }

    /**
     * @throws ConfigurationException
     * @throws \Exception
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_copying_headers_from_input_message()
    {
        $outputChannel = QueueChannel::create();
        $enricher           = EnricherBuilder::create("some")
            ->withPropertySetters([
                StaticSetterBuilder::createWith("token", "123")
            ])
            ->build(
            InMemoryChannelResolver::createEmpty(),
            InMemoryReferenceSearchService::createEmpty()
        );

        $payload = [];
        $enricher->handle(
            $this->createMessageWith($payload, $outputChannel)
                ->setHeader("user", 1)
                ->build()
        );

        $this->assertEquals(1, $outputChannel->receive()->getHeaders()->get("user"));
    }

    /**
     * @throws ConfigurationException
     * @throws \Exception
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_enriching_with_multiple_static_headers()
    {
        $outputChannel = QueueChannel::create();
        $enricher           = EnricherBuilder::create("some")
            ->withHeaderSetters([
                StaticHeaderSetterBuilder::create("token", "123")
            ])
            ->build(
                InMemoryChannelResolver::createEmpty(),
                InMemoryReferenceSearchService::createEmpty()
            );

        $enricher->handle(
            $this->createMessageWith("some", $outputChannel)
                ->build()
        );

        $this->assertEquals(
            "123",
            $outputChannel->receive()->getHeaders()->get("token")
        );
    }

    /**
     * @param $payload
     * @param $outputChannel
     *
     * @return MessageBuilder
     */
    private function createMessageWith($payload, $outputChannel): MessageBuilder
    {
        return MessageBuilder::withPayload($payload)->setReplyChannel($outputChannel);
    }
}