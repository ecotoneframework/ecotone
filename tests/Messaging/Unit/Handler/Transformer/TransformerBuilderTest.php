<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler\Transformer;

use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\Transformer\TransformerBuilder;
use Ecotone\Test\ComponentTestBuilder;
use Test\Ecotone\Messaging\Fixture\Service\CalculatingService;
use Test\Ecotone\Messaging\Unit\MessagingTestCase;

/**
 * TransformerBuilder::createHeaderEnricher(), createHeaderMapper(), and
 * createWithExpression() have no #[Transformer] attribute equivalent -- the
 * attribute always wraps a real PHP method. TransformerAttributeTest covers
 * the user-reachable method-backed transformation behaviour via #[Transformer].
 *
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
class TransformerBuilderTest extends MessagingTestCase
{
    public function test_transforming_with_header_enricher()
    {
        $payload = 'someBigPayload';
        $headerValue = 'abc';
        $messaging = ComponentTestBuilder::create()
            ->withMessageHandler(
                TransformerBuilder::createHeaderEnricher([
                    'token' => $headerValue,
                    'correlation-id' => 1,
                ])
                    ->withInputChannelName($inputChannel = 'inputChannel')
            )
            ->build();

        $message = $messaging->sendDirectToChannelWithMessageReply($inputChannel, $payload);

        $this->assertEquals($payload, $message->getPayload());
        $this->assertEquals($headerValue, $message->getHeaders()->get('token'));
        $this->assertEquals(1, $message->getHeaders()->get('correlation-id'));
    }

    public function test_transforming_with_header_mapper()
    {
        $payload = 'someBigPayload';
        $headerValue = 'abc';
        $messaging = ComponentTestBuilder::create()
            ->withMessageHandler(
                TransformerBuilder::createHeaderMapper([
                    'token' => 'secret',
                ])
                    ->withInputChannelName($inputChannel = 'inputChannel')
            )
            ->build();

        $message = $messaging->sendDirectToChannelWithMessageReply($inputChannel, $payload, metadata: ['token' => $headerValue]);

        $this->assertEquals($payload, $message->getPayload());
        $this->assertEquals($headerValue, $message->getHeaders()->get('token'));
        $this->assertEquals($headerValue, $message->getHeaders()->get('secret'));
    }

    public function test_transforming_payload_using_expression()
    {
        $payload = 1;
        $messaging = ComponentTestBuilder::create()
            ->withMessageHandler(
                TransformerBuilder::createWithExpression('payload + 3')
                    ->withInputChannelName($inputChannel = 'inputChannel')
            )
            ->build();

        $message = $messaging->sendDirectToChannelWithMessageReply($inputChannel, $payload);

        $this->assertEquals($payload + 3, $message->getPayload());
    }

    public function test_converting_to_string()
    {
        $inputChannelName = 'inputChannel';
        $endpointName = 'someName';

        $this->assertIsString(
            (string)TransformerBuilder::create('ref-name', InterfaceToCall::create(CalculatingService::class, 'result'))
                ->withInputChannelName($inputChannelName)
                ->withEndpointId($endpointName)
        );
    }
}
