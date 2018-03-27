<?php

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Handler\Transformer;

use Fixture\Handler\ReplyViaHeadersMessageHandler;
use SimplyCodedSoftware\IntegrationMessaging\Channel\DirectChannel;
use SimplyCodedSoftware\IntegrationMessaging\Channel\QueueChannel;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationException;
use SimplyCodedSoftware\IntegrationMessaging\Config\InMemoryChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\EnricherBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\PropertySetter\StaticPropertySetterBuilder;
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
    public function test_throwing_exception_if_no_property_setters_configured()
    {
        $this->expectException(ConfigurationException::class);

        $enricher = EnricherBuilder::create("some",  []);
        $enricher->build(InMemoryChannelResolver::createEmpty(), InMemoryReferenceSearchService::createEmpty());
    }

    public function test_enriching_array_payload_with_static_property_setter()
    {
        $outputChannel = QueueChannel::create();
        $enricher           = EnricherBuilder::create("some",  [
            StaticPropertySetterBuilder::createWith("token", "123")
        ])->build(
            InMemoryChannelResolver::createEmpty(),
            InMemoryReferenceSearchService::createEmpty()
        );

        $payload = [];
        $enricher->handle($this->createMessageWith($payload, $outputChannel)->build());

        $this->assertEquals(
            [
                "token" => 123
            ],
            $outputChannel->receive()->getPayload()
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