<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler\Splitter;

use Ecotone\Api\Attribute\InternalHandler;
use Ecotone\Api\Attribute\Splitter;
use Ecotone\Api\ExtensionObject\MediaType;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Messaging\Message;
use Ecotone\Api\Messaging\MessageHeaders;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Support\MessageBuilder;
use PHPUnit\Framework\TestCase;

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class SplitterAttributeTest extends TestCase
{
    public function test_splitting_array_payload_into_one_message_per_element(): void
    {
        $handler = new SplittingHandler();
        $ecotone = EcotoneLite::bootstrapFlowTesting([SplittingHandler::class], [$handler], configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]));

        $ecotone->sendDirectToChannel(SplittingHandler::PAYLOAD_CHANNEL, [1, 2, 3, 4]);

        $capturedMessages = $handler->capturedPayloadMessages;
        $this->assertCount(4, $capturedMessages);

        $messageIds = [];
        foreach ($capturedMessages as $index => $splitMessage) {
            $sequenceNumber = $index + 1;
            $this->assertSame($sequenceNumber, $splitMessage->getPayload());
            $this->assertSame(4, $splitMessage->getHeaders()->get(MessageHeaders::SEQUENCE_SIZE));
            $this->assertSame($sequenceNumber, $splitMessage->getHeaders()->get(MessageHeaders::SEQUENCE_NUMBER));
            $this->assertEquals(
                MediaType::createApplicationXPHPWithTypeParameter(Type::createFromVariable($sequenceNumber)->toString()),
                $splitMessage->getHeaders()->getContentType()
            );
            $messageIds[] = $splitMessage->getHeaders()->getMessageId();
        }
        $this->assertCount(4, array_unique($messageIds));
    }

    public function test_splitting_when_service_already_returns_built_messages(): void
    {
        $handler = new SplittingHandler();
        $ecotone = EcotoneLite::bootstrapFlowTesting([SplittingHandler::class], [$handler], configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]));

        $ecotone->sendDirectToChannel(SplittingHandler::MESSAGES_CHANNEL, ['some1', 'some2'], [
            'token' => 'abcd',
        ]);

        $this->assertCount(2, $handler->capturedMessagesMessages);
        [$first, $second] = $handler->capturedMessagesMessages;

        $this->assertSame('some1', $first->getPayload());
        $this->assertSame('some2', $second->getPayload());
        $this->assertSame('abcd', $first->getHeaders()->get('token'));
        $this->assertSame($first->getHeaders()->get(MessageHeaders::MESSAGE_CORRELATION_ID), $second->getHeaders()->get(MessageHeaders::MESSAGE_CORRELATION_ID));
        $this->assertNotSame($first->getHeaders()->getMessageId(), $second->getHeaders()->getMessageId());
    }

    public function test_throws_when_splitter_method_does_not_return_an_array(): void
    {
        $this->expectException(\Ecotone\Messaging\Support\InvalidArgumentException::class);

        EcotoneLite::bootstrapFlowTesting([WrongSplittingHandler::class], [new WrongSplittingHandler()], configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]));
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class SplittingHandler
{
    public const PAYLOAD_CHANNEL = 'splitter.payload';
    public const PAYLOAD_OUTPUT_CHANNEL = 'splitter.payload.output';
    public const MESSAGES_CHANNEL = 'splitter.messages';
    public const MESSAGES_OUTPUT_CHANNEL = 'splitter.messages.output';

    /** @var Message[] */
    public array $capturedPayloadMessages = [];

    /** @var Message[] */
    public array $capturedMessagesMessages = [];

    #[Splitter(self::PAYLOAD_CHANNEL, outputChannelName: self::PAYLOAD_OUTPUT_CHANNEL)]
    public function splitToPayload(Message $message): array
    {
        return $message->getPayload();
    }

    #[InternalHandler(self::PAYLOAD_OUTPUT_CHANNEL)]
    public function capturePayload(Message $message): void
    {
        $this->capturedPayloadMessages[] = $message;
    }

    #[Splitter(self::MESSAGES_CHANNEL, outputChannelName: self::MESSAGES_OUTPUT_CHANNEL)]
    public function splitToMessages(Message $message): array
    {
        $splitMessages = [];
        foreach ($message->getPayload() as $value) {
            $splitMessages[] = MessageBuilder::withPayload($value)
                ->setHeader('token', $message->getHeaders()->get('token'))
                ->build();
        }

        return $splitMessages;
    }

    #[InternalHandler(self::MESSAGES_OUTPUT_CHANNEL)]
    public function captureMessages(Message $message): void
    {
        $this->capturedMessagesMessages[] = $message;
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class WrongSplittingHandler
{
    #[Splitter('splitter.wrong')]
    public function splitWithReturnString(): string
    {
        return 'some';
    }
}
