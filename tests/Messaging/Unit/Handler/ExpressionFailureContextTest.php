<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler;

use Ecotone\Api\Attribute\AddHeader;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\Header;
use Ecotone\Api\Attribute\Payload;
use Ecotone\Api\Attribute\QueryHandler;
use Ecotone\Api\Attribute\Reference;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Lite\Test\FlowTestSupport;
use Ecotone\Messaging\Handler\ExpressionEvaluationException;
use Ecotone\Messaging\Handler\MethodInvocationException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * licence Apache-2.0
 * @internal
 */
final class ExpressionFailureContextTest extends TestCase
{
    public function test_payload_expression_syntax_error_names_attribute_parameter_handler_and_expression(): void
    {
        $this->expectException(MethodInvocationException::class);
        $this->expectExceptionMessage(
            '#[Payload] on $value in ' . ExpressionFailureService::class . '::withBrokenPayloadExpression failed.'
            . ' Expression: payload..broken(((.'
            . ' Unclosed "(" around position 17 for expression `payload..broken(((`.'
        );

        $this->bootstrap()->sendCommandWithRouting('expressionFailure.payload', 'order-1');
    }

    public function test_header_expression_naming_an_unknown_service_names_attribute_parameter_handler_and_expression(): void
    {
        $this->expectException(MethodInvocationException::class);
        $this->expectExceptionMessage(
            '#[Header] on $token in ' . ExpressionFailureService::class . '::withUnknownReferenceInHeaderExpression failed.'
            . " Expression: reference('missingMapper').map(value)."
            . ' Reference missingMapper was not found in definitions'
        );

        $this->bootstrap()->sendCommandWithRouting('expressionFailure.header', 'order-1', metadata: ['token' => 'abc']);
    }

    public function test_reference_expression_calling_a_missing_method_names_attribute_parameter_handler_and_expression(): void
    {
        $this->expectException(MethodInvocationException::class);
        $this->expectExceptionMessage(
            '#[Reference] on $mapper in ' . ExpressionFailureService::class . '::withBrokenReferenceExpression failed.'
            . ' Expression: service.notThere(payload).'
        );

        $this->bootstrap()->sendCommandWithRouting('expressionFailure.reference', 'order-1');
    }

    public function test_service_throwing_inside_an_expression_keeps_its_exception_as_previous(): void
    {
        try {
            $this->bootstrap()->sendCommandWithRouting('expressionFailure.throwingService', 'order-1');
            $this->fail('Expected expression evaluation to fail');
        } catch (MethodInvocationException $exception) {
            $expressionFailure = $exception->getPrevious();
            $this->assertInstanceOf(ExpressionEvaluationException::class, $expressionFailure);
            $this->assertSame(
                '#[Payload] on $mapped in ' . ExpressionFailureService::class . '::withThrowingService failed.'
                . " Expression: reference('expressionFailureMapper').blowUp(payload)."
                . ' mapper blew up',
                $expressionFailure->getMessage(),
            );
            $this->assertInstanceOf(ExpressionFailureMapperException::class, $expressionFailure->getPrevious());
        }
    }

    public function test_required_header_missing_for_a_header_expression_names_the_header_and_the_expression(): void
    {
        $this->expectException(MethodInvocationException::class);
        $this->expectExceptionMessage(
            '#[Header] on $token in ' . ExpressionFailureService::class . '::withUnknownReferenceInHeaderExpression failed.'
            . " Expression: reference('missingMapper').map(value)."
            . " Header 'token' is not available in the message, and the parameter does not allow null."
        );

        $this->bootstrap()->sendCommandWithRouting('expressionFailure.header', 'order-1');
    }

    public function test_add_header_expression_failure_names_the_attribute_and_the_method_it_is_declared_on(): void
    {
        $this->expectExceptionMessage(
            '#[AddHeader] in ' . ExpressionFailureService::class . '::withBrokenAddHeaderExpression failed.'
            . ' Expression: payload..broken(((.'
            . ' Unclosed "(" around position 17 for expression `payload..broken(((`.'
        );

        $this->bootstrap()->sendCommandWithRouting('expressionFailure.addHeader', 'order-1');
    }

    public function test_working_expression_keeps_its_value_and_is_evaluated_once_per_parameter(): void
    {
        $mapper = new ExpressionFailureMapper();

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [ExpressionFailureService::class],
            ['expressionFailureMapper' => $mapper, ExpressionFailureService::class => new ExpressionFailureService()],
            configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]),
        );

        $this->assertSame('MAPPED order-1', $ecotone->sendQueryWithRouting('expressionFailure.working', 'order-1'));
        $this->assertSame(1, $mapper->mapCallCount);
    }

    private function bootstrap(): FlowTestSupport
    {
        return EcotoneLite::bootstrapFlowTesting(
            [ExpressionFailureService::class],
            ['expressionFailureMapper' => new ExpressionFailureMapper(), ExpressionFailureService::class => new ExpressionFailureService()],
            configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]),
        );
    }
}

final class ExpressionFailureMapper
{
    public int $mapCallCount = 0;

    public function map(string $value): string
    {
        $this->mapCallCount++;

        return 'MAPPED ' . $value;
    }

    public function blowUp(string $value): string
    {
        throw new ExpressionFailureMapperException('mapper blew up');
    }
}

final class ExpressionFailureMapperException extends RuntimeException
{
}

final class ExpressionFailureService
{
    #[CommandHandler('expressionFailure.payload')]
    public function withBrokenPayloadExpression(#[Payload('payload..broken(((')] string $value): void
    {
    }

    #[CommandHandler('expressionFailure.header')]
    public function withUnknownReferenceInHeaderExpression(#[Header('token', "reference('missingMapper').map(value)")] string $token): void
    {
    }

    #[CommandHandler('expressionFailure.reference')]
    public function withBrokenReferenceExpression(#[Reference('expressionFailureMapper', 'service.notThere(payload)')] string $mapper): void
    {
    }

    #[CommandHandler('expressionFailure.throwingService')]
    public function withThrowingService(#[Payload("reference('expressionFailureMapper').blowUp(payload)")] string $mapped): void
    {
    }

    #[CommandHandler('expressionFailure.addHeader')]
    #[AddHeader('mappedOrder', expression: 'payload..broken(((')]
    public function withBrokenAddHeaderExpression(string $orderId): void
    {
    }

    #[QueryHandler('expressionFailure.working')]
    public function withWorkingExpression(#[Payload("reference('expressionFailureMapper').map(payload)")] string $mapped): string
    {
        return $mapped;
    }
}
