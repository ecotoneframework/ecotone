<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Filter;

use Fixture\Handler\Selector\MessageSelectorExample;
use SimplyCodedSoftware\IntegrationMessaging\Channel\QueueChannel;
use SimplyCodedSoftware\IntegrationMessaging\Config\InMemoryChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\MessagingException;
use SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException;
use SimplyCodedSoftware\IntegrationMessaging\Support\MessageBuilder;
use Test\SimplyCodedSoftware\IntegrationMessaging\MessagingTest;

/**
 * Class MessageFilterBuilderTest
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Filter
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MessageFilterBuilderTest extends MessagingTest
{
    public function test_forwarding_message_if_selector_returns_true()
    {
        $outputChannelName = "outputChannel";
        $outputChannel = QueueChannel::create();

        $messageFilter = MessageFilterBuilder::createWithReferenceName("inputChannel", $outputChannelName, MessageSelectorExample::class, "accept")
                            ->build(
                                InMemoryChannelResolver::createFromAssociativeArray([
                                    $outputChannelName => $outputChannel
                                ]),
                                InMemoryReferenceSearchService::createWith([
                                    MessageSelectorExample::class => MessageSelectorExample::create()
                                ])
                            );

        $message = MessageBuilder::withPayload("some")->build();

        $messageFilter->handle($message);

        $this->assertEquals(
            $message,
            $outputChannel->receive()
        );
    }

    public function test_discard_message_if_selector_returns_false()
    {
        $outputChannelName = "outputChannel";
        $outputChannel = QueueChannel::create();

        $messageFilter = MessageFilterBuilder::createWithReferenceName("inputChannel", $outputChannelName, MessageSelectorExample::class, "refuse")
            ->build(
                InMemoryChannelResolver::createFromAssociativeArray([
                    $outputChannelName => $outputChannel
                ]),
                InMemoryReferenceSearchService::createWith([
                    MessageSelectorExample::class => MessageSelectorExample::create()
                ])
            );

        $message = MessageBuilder::withPayload("some")->build();

        $messageFilter->handle($message);

        $this->assertNull($outputChannel->receive());
    }

    public function test_throwing_exception_if_selector_return_type_is_different_than_boolean()
    {
        $outputChannelName = "outputChannel";
        $outputChannel = QueueChannel::create();

        $this->expectException(InvalidArgumentException::class);

        MessageFilterBuilder::createWithReferenceName("inputChannel", $outputChannelName, MessageSelectorExample::class, "wrongReturnType")
            ->build(
                InMemoryChannelResolver::createFromAssociativeArray([
                    $outputChannelName => $outputChannel
                ]),
                InMemoryReferenceSearchService::createWith([
                    MessageSelectorExample::class => MessageSelectorExample::create()
                ])
            );
    }

    public function test_publishing_message_to_discard_channel_if_defined()
    {
        $outputChannelName = "outputChannel";
        $outputChannel = QueueChannel::create();
        $discardChannelName = "discardChannel";
        $discardChannel = QueueChannel::create();

        $messageFilter = MessageFilterBuilder::createWithReferenceName("inputChannel", $outputChannelName, MessageSelectorExample::class, "refuse")
            ->withDiscardChannelName($discardChannelName)
            ->build(
                InMemoryChannelResolver::createFromAssociativeArray([
                    $outputChannelName => $outputChannel,
                    $discardChannelName => $discardChannel
                ]),
                InMemoryReferenceSearchService::createWith([
                    MessageSelectorExample::class => MessageSelectorExample::create()
                ])
            );

        $message = MessageBuilder::withPayload("some")->build();

        $messageFilter->handle($message);

        $this->assertNull($outputChannel->receive());
        $this->assertEquals($message, $discardChannel->receive());
    }

    public function test_throwing_exception_on_discard_if_defined()
    {
        $outputChannelName = "outputChannel";
        $outputChannel = QueueChannel::create();

        $messageFilter = MessageFilterBuilder::createWithReferenceName("inputChannel", $outputChannelName, MessageSelectorExample::class, "refuse")
            ->withThrowingExceptionOnDiscard(true)
            ->build(
                InMemoryChannelResolver::createFromAssociativeArray([
                    $outputChannelName => $outputChannel
                ]),
                InMemoryReferenceSearchService::createWith([
                    MessageSelectorExample::class => MessageSelectorExample::create()
                ])
            );

        $message = MessageBuilder::withPayload("some")->build();

        $this->expectException(MessagingException::class);

        $messageFilter->handle($message);
    }
}