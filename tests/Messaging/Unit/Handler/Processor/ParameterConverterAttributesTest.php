<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler\Processor;

use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\ConfigurationVariable;
use Ecotone\Api\Attribute\Header;
use Ecotone\Api\Attribute\Headers;
use Ecotone\Api\Attribute\Payload;
use Ecotone\Api\Attribute\QueryHandler;
use Ecotone\Api\Attribute\Reference;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Messaging\Handler\MethodInvocationException;
use Ecotone\Messaging\Support\InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class ParameterConverterAttributesTest extends TestCase
{
    public function test_payload_converter_passes_the_full_message_payload(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [RecordingParameterConverterHandler::class],
            [new RecordingParameterConverterHandler()],
            enabledModules: [],
        );

        $ecotone->sendCommandWithRouting('converter.payload', 100);

        self::assertSame(100, $ecotone->sendQueryWithRouting('converter.lastValue'));
    }

    public function test_payload_expression_converter_evaluates_expression_against_the_payload(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [RecordingParameterConverterHandler::class],
            [new RecordingParameterConverterHandler()],
            enabledModules: [],
        );

        $ecotone->sendCommandWithRouting('converter.payloadExpression', 100);

        self::assertSame('1001', $ecotone->sendQueryWithRouting('converter.lastValue'));
    }

    public function test_header_converter_extracts_the_named_header(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [RecordingParameterConverterHandler::class],
            [new RecordingParameterConverterHandler()],
            enabledModules: [],
        );

        $ecotone->sendCommandWithRouting('converter.header', metadata: ['token' => 100]);

        self::assertSame(100, $ecotone->sendQueryWithRouting('converter.lastValue'));
    }

    public function test_required_header_converter_throws_when_header_is_missing(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [RecordingParameterConverterHandler::class],
            [new RecordingParameterConverterHandler()],
            enabledModules: [],
        );

        $this->expectException(MethodInvocationException::class);

        $ecotone->sendCommandWithRouting('converter.header');
    }

    public function test_optional_header_converter_is_null_when_header_is_missing(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [RecordingParameterConverterHandler::class],
            [new RecordingParameterConverterHandler()],
            enabledModules: [],
        );

        $ecotone->sendCommandWithRouting('converter.optionalHeader');

        self::assertNull($ecotone->sendQueryWithRouting('converter.lastValue'));
    }

    public function test_header_expression_converter_evaluates_expression_against_the_header_value(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [RecordingParameterConverterHandler::class],
            [new RecordingParameterConverterHandler()],
            enabledModules: [],
        );

        $ecotone->sendCommandWithRouting('converter.headerExpression', metadata: ['token' => 100]);

        self::assertSame('1001', $ecotone->sendQueryWithRouting('converter.lastValue'));
    }

    public function test_headers_converter_returns_all_message_headers(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [RecordingParameterConverterHandler::class],
            [new RecordingParameterConverterHandler()],
            enabledModules: [],
        );

        $ecotone->sendCommandWithRouting('converter.allHeaders', metadata: ['some' => 'test']);

        $headers = $ecotone->sendQueryWithRouting('converter.lastValue');
        self::assertSame('test', $headers['some']);
    }

    public function test_reference_converter_resolves_a_registered_reference_by_name(): void
    {
        $referencedService = new stdClass();
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [RecordingParameterConverterHandler::class],
            [new RecordingParameterConverterHandler(), 'referencedService' => $referencedService],
            enabledModules: [],
        );

        $ecotone->sendCommandWithRouting('converter.reference');

        self::assertSame($referencedService, $ecotone->sendQueryWithRouting('converter.lastValue'));
    }

    public function test_reference_converter_with_expression_evaluates_against_the_resolved_reference(): void
    {
        $referencedService = new NamedReferenceService('someName');
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [RecordingParameterConverterHandler::class],
            [new RecordingParameterConverterHandler(), 'referencedService' => $referencedService],
            enabledModules: [],
        );

        $ecotone->sendCommandWithRouting('converter.referenceExpression');

        self::assertSame('someName', $ecotone->sendQueryWithRouting('converter.lastValue'));
    }

    public function test_configuration_variable_converter_retrieves_value_by_explicit_name(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [RecordingParameterConverterHandler::class],
            [new RecordingParameterConverterHandler()],
            enabledModules: [],
            configurationVariables: ['appName' => 'ecotone-app'],
        );

        $ecotone->sendCommandWithRouting('converter.configurationVariable');

        self::assertSame('ecotone-app', $ecotone->sendQueryWithRouting('converter.lastValue'));
    }

    public function test_configuration_variable_converter_falls_back_to_the_parameter_default_when_missing(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [RecordingParameterConverterHandler::class],
            [new RecordingParameterConverterHandler()],
            enabledModules: [],
        );

        $ecotone->sendCommandWithRouting('converter.configurationVariableWithDefault');

        self::assertSame('default-app', $ecotone->sendQueryWithRouting('converter.lastValue'));
    }

    public function test_configuration_variable_converter_throws_when_required_variable_is_missing(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [RequiredMissingConfigurationVariableHandler::class],
            [new RequiredMissingConfigurationVariableHandler()],
            enabledModules: [],
        );

        $this->expectException(InvalidArgumentException::class);

        $ecotone->sendCommandWithRouting('converter.requiredMissing');
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class RecordingParameterConverterHandler
{
    private mixed $lastValue = null;

    #[CommandHandler('converter.payload')]
    public function payload(#[Payload] mixed $value): void
    {
        $this->lastValue = $value;
    }

    #[CommandHandler('converter.payloadExpression')]
    public function payloadExpression(#[Payload('value ~ 1')] mixed $value): void
    {
        $this->lastValue = $value;
    }

    #[CommandHandler('converter.header')]
    public function header(#[Header('token')] int $value): void
    {
        $this->lastValue = $value;
    }

    #[CommandHandler('converter.optionalHeader')]
    public function optionalHeader(#[Header('token')] mixed $value = null): void
    {
        $this->lastValue = $value;
    }

    #[CommandHandler('converter.headerExpression')]
    public function headerExpression(#[Header('token', 'value ~ 1')] mixed $value): void
    {
        $this->lastValue = $value;
    }

    #[CommandHandler('converter.allHeaders')]
    public function allHeaders(#[Headers] array $headers): void
    {
        $this->lastValue = $headers;
    }

    #[CommandHandler('converter.reference')]
    public function reference(#[Reference('referencedService')] mixed $value): void
    {
        $this->lastValue = $value;
    }

    #[CommandHandler('converter.referenceExpression')]
    public function referenceExpression(#[Reference('referencedService', 'service.getName()')] mixed $value): void
    {
        $this->lastValue = $value;
    }

    #[CommandHandler('converter.configurationVariable')]
    public function configurationVariable(#[ConfigurationVariable('appName')] string $value): void
    {
        $this->lastValue = $value;
    }

    #[CommandHandler('converter.configurationVariableWithDefault')]
    public function configurationVariableWithDefault(#[ConfigurationVariable('missingAppName')] string $value = 'default-app'): void
    {
        $this->lastValue = $value;
    }

    #[QueryHandler('converter.lastValue')]
    public function getLastValue(): mixed
    {
        return $this->lastValue;
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class NamedReferenceService
{
    public function __construct(private string $name)
    {
    }

    public function getName(): string
    {
        return $this->name;
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class RequiredMissingConfigurationVariableHandler
{
    #[CommandHandler('converter.requiredMissing')]
    public function handle(#[ConfigurationVariable('mustExist')] string $value): void
    {
    }
}
