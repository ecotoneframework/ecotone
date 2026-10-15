<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler\Transformer;

use Ecotone\Api\Attribute\Header;
use Ecotone\Api\Attribute\Payload;
use Ecotone\Api\Attribute\Transformer;
use Ecotone\Api\ExtensionObject\MediaType;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Lite\EcotoneLite;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class TransformerAttributeTest extends TestCase
{
    public function test_modifying_the_payload(): void
    {
        $ecotone = $this->bootstrap();

        $this->assertSame('transformed', $ecotone->sendDirectToChannel(TransformerHandler::MODIFY_CHANNEL, 'some'));
    }

    public function test_default_transformation_returns_the_result_unchanged(): void
    {
        $ecotone = $this->bootstrap();

        $this->assertEquals(new stdClass(), $ecotone->sendDirectToChannelWithMessageReply(TransformerHandler::ECHO_CHANNEL, new stdClass())->getPayload());
    }

    public function test_throws_at_bootstrap_when_transforming_method_is_void(): void
    {
        $this->expectException(\Ecotone\Messaging\Support\InvalidArgumentException::class);

        EcotoneLite::bootstrapFlowTesting([VoidTransformerHandler::class], [new VoidTransformerHandler()], configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]));
    }

    public function test_message_is_not_sent_to_output_channel_when_transformer_returns_null(): void
    {
        $ecotone = $this->bootstrap();

        $ecotone->sendDirectToChannel(TransformerHandler::NULL_CHANNEL, 'test');

        $this->assertNull($ecotone->receiveMessageFrom(TransformerHandler::NULL_OUTPUT_CHANNEL));
    }

    public function test_array_returned_from_non_array_payload_is_treated_as_header_enrichment(): void
    {
        $ecotone = $this->bootstrap();

        $message = $ecotone->sendDirectToChannelWithMessageReply(TransformerHandler::ARRAY_HEADERS_CHANNEL, 'test');

        $this->assertSame('test', $message->getHeaders()->get('some'));
    }

    public function test_array_returned_from_array_payload_is_also_treated_as_header_enrichment(): void
    {
        $ecotone = $this->bootstrap();

        $payload = ['some' => 'some payload'];
        $message = $ecotone->sendDirectToChannelWithMessageReply(TransformerHandler::ARRAY_PAYLOAD_CHANNEL, $payload);

        $this->assertSame('some payload', $message->getHeaders()->get('some'));
    }

    public function test_transforming_with_explicit_header_and_payload_converters(): void
    {
        $ecotone = $this->bootstrap();

        $message = $ecotone->sendDirectToChannelWithMessageReply(TransformerHandler::CUSTOM_CONVERTERS_CHANNEL, 'someBigPayload', [
            'token' => 'abc',
        ]);

        $this->assertSame('someBigPayloadabc', $message->getPayload());
        $this->assertSame('abc', $message->getHeaders()->get('token'));
    }

    public function test_transformer_result_content_type_reflects_the_returned_type(): void
    {
        $ecotone = $this->bootstrap();

        $message = $ecotone->sendDirectToChannelWithMessageReply(TransformerHandler::RETURN_TYPE_CHANNEL, 'johny');

        $this->assertEquals(MediaType::createApplicationXPHPWithTypeParameter('string'), $message->getHeaders()->getContentType());
    }

    private function bootstrap()
    {
        return EcotoneLite::bootstrapFlowTesting(
            [TransformerHandler::class],
            [new TransformerHandler()],
            ServiceConfiguration::createWithDefaults()->withExtensionObjects([
                SimpleMessageChannelBuilder::createQueueChannel(TransformerHandler::NULL_OUTPUT_CHANNEL),
            ])
                ->withEnabledModulePackageNames([]),
        );
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class TransformerHandler
{
    public const MODIFY_CHANNEL = 'transformer.modify';
    public const ECHO_CHANNEL = 'transformer.echo';
    public const NULL_CHANNEL = 'transformer.null';
    public const NULL_OUTPUT_CHANNEL = 'transformer.null.output';
    public const ARRAY_HEADERS_CHANNEL = 'transformer.arrayHeaders';
    public const ARRAY_PAYLOAD_CHANNEL = 'transformer.arrayPayload';
    public const CUSTOM_CONVERTERS_CHANNEL = 'transformer.customConverters';
    public const RETURN_TYPE_CHANNEL = 'transformer.returnType';

    #[Transformer(self::MODIFY_CHANNEL)]
    public function modify(string $payload): string
    {
        return 'transformed';
    }

    #[Transformer(self::ECHO_CHANNEL)]
    public function echoBack(mixed $payload): mixed
    {
        return $payload;
    }

    #[Transformer(self::NULL_CHANNEL, outputChannelName: self::NULL_OUTPUT_CHANNEL)]
    public function returnsNull(string $payload): ?string
    {
        return null;
    }

    #[Transformer(self::ARRAY_HEADERS_CHANNEL)]
    public function withArrayReturnValue(string $name): array
    {
        return ['some' => $name];
    }

    #[Transformer(self::ARRAY_PAYLOAD_CHANNEL)]
    public function withArrayTypeHintAndArrayReturnValue(array $values): array
    {
        return $values;
    }

    #[Transformer(self::CUSTOM_CONVERTERS_CHANNEL)]
    public function withCustomConverters(
        #[Payload] string $name,
        #[Header('token')] string $surname,
    ): string {
        return $name . $surname;
    }

    #[Transformer(self::RETURN_TYPE_CHANNEL)]
    public function getName(string $payload): string
    {
        return $payload;
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class VoidTransformerHandler
{
    #[Transformer('transformer.void')]
    public function setName(string $payload): void
    {
    }
}
