<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler\Transformer;

use Ecotone\Messaging\Handler\Enricher\Converter\EnrichHeaderWithExpressionBuilder;
use Ecotone\Messaging\Handler\Enricher\Converter\EnrichPayloadWithExpressionBuilder;
use Ecotone\Messaging\Handler\Enricher\EnricherBuilder;
use Ecotone\Messaging\Handler\ExpressionEvaluationException;
use Ecotone\Messaging\Handler\Transformer\TransformerBuilder;
use Ecotone\Test\ComponentTestBuilder;
use PHPUnit\Framework\TestCase;

/**
 * licence Apache-2.0
 * @internal
 */
final class EnricherAndTransformerExpressionFailureTest extends TestCase
{
    public function test_transformer_expression_syntax_error_names_the_step_the_endpoint_and_the_expression(): void
    {
        $messaging = ComponentTestBuilder::create()
            ->withMessageHandler(
                TransformerBuilder::createWithExpression('payload..broken(((')
                    ->withInputChannelName('transformerInputChannel')
            )
            ->build();

        $this->expectException(ExpressionEvaluationException::class);
        $this->expectExceptionMessage(
            "Transformer in endpoint 'transformerInputChannel' failed."
            . ' Expression: payload..broken(((.'
            . ' Unclosed "(" around position 17 for expression `payload..broken(((`.'
        );

        $messaging->sendDirectToChannel('transformerInputChannel', 1);
    }

    public function test_enriching_payload_with_a_broken_expression_names_the_enriched_path_and_the_expression(): void
    {
        $messaging = ComponentTestBuilder::create()
            ->withMessageHandler(
                EnricherBuilder::create([EnrichPayloadWithExpressionBuilder::createWith('token', 'payload..broken(((')])
                    ->withInputChannelName('enricherInputChannel')
            )
            ->build();

        $this->expectException(ExpressionEvaluationException::class);
        $this->expectExceptionMessage(
            "Enricher on payload path 'token' failed."
            . ' Expression: payload..broken(((.'
            . ' Unclosed "(" around position 17 for expression `payload..broken(((`.'
        );

        $messaging->sendDirectToChannel('enricherInputChannel', []);
    }

    public function test_enriching_header_with_a_broken_expression_names_the_enriched_path_and_the_expression(): void
    {
        $messaging = ComponentTestBuilder::create()
            ->withMessageHandler(
                EnricherBuilder::create([EnrichHeaderWithExpressionBuilder::createWith('token', "reference('missingMapper').map(payload)")])
                    ->withInputChannelName('headerEnricherInputChannel')
            )
            ->build();

        $this->expectException(ExpressionEvaluationException::class);
        $this->expectExceptionMessage(
            "Enricher on header path 'token' failed."
            . " Expression: reference('missingMapper').map(payload)."
            . ' Reference missingMapper was not found in definitions'
        );

        $messaging->sendDirectToChannel('headerEnricherInputChannel', []);
    }
}
