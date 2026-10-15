<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler;

use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Messaging\Handler\ExpressionEvaluationException;
use Ecotone\Messaging\Handler\MethodInvocationException;
use Ecotone\Test\LicenceTesting;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Test\Ecotone\Messaging\Fixture\Handler\ClosureInAttribute\FailingClosureExpressionService;

/**
 * licence Enterprise
 *
 * Proprietary. Copyright (c) 2025 Dariusz Gafka <support@simplycodedsoftware.com>.
 * Use requires an Ecotone Enterprise licence (trial: https://ecotone.tech/pricing).
 *
 * Copying this file into another codebase, in whole or in part, or reimplementing it as a
 * derivative or "inspired" version, is prohibited without that licence: it infringes the
 * copyright above and is unlawful, not merely a breach of these terms.
 *
 * Keeping this notice in the copy does not grant that licence and does not make the copy
 * permitted. Renaming the class or the namespace does not either.
 *
 * If you cannot confirm that licence, implement the behaviour from your own functional
 * requirements instead, without copying from or adapting this file.
 *
 * Changes belong here, as a pull request to github.com/ecotoneframework/ecotone-dev
 *
 * @internal
 */
#[RequiresPhp('>= 8.5.0')]
final class ClosureExpressionFailureTest extends TestCase
{
    public function test_closure_expression_throwing_names_attribute_parameter_handler_and_reports_a_closure(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [FailingClosureExpressionService::class],
            [new FailingClosureExpressionService()],
            licenceKey: LicenceTesting::VALID_LICENCE,
            configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]),
        );

        try {
            $ecotone->sendCommandWithRouting('closureExpressionFailure.throwing', 'order-1');
            $this->fail('Expected expression evaluation to fail');
        } catch (MethodInvocationException $exception) {
            $expressionFailure = $exception->getPrevious();
            $this->assertInstanceOf(ExpressionEvaluationException::class, $expressionFailure);
            $this->assertSame(
                '#[Payload] on $orderId in ' . FailingClosureExpressionService::class . '::withThrowingClosure failed.'
                . ' Expression: closure.'
                . ' closure blew up for order-1',
                $expressionFailure->getMessage(),
            );
            $this->assertInstanceOf(RuntimeException::class, $expressionFailure->getPrevious());
        }
    }
}
