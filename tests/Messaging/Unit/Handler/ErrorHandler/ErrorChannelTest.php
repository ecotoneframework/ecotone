<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler\ErrorHandler;

use Ecotone\Lite\EcotoneLite;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Config\ModulePackageList;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Messaging\Endpoint\ExecutionPollingMetadata;
use Ecotone\Messaging\Endpoint\FinalFailureStrategy;
use Ecotone\Messaging\Handler\Recoverability\ErrorHandlerConfiguration;
use Ecotone\Messaging\Handler\Recoverability\RetryTemplateBuilder;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\Messaging\Fixture\Handler\ErrorChannel\OrderService;

/**
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
final class ErrorChannelTest extends TestCase
{
    public function test_exception_handling_with_retries_without_dead_letter_uses_final_failure_strategy(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [OrderService::class],
            [new OrderService()],
            ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackagesExcept([ModulePackageList::ASYNCHRONOUS_PACKAGE]))
                ->withNamespaces(['Test\Ecotone\Messaging\Fixture\Handler\ErrorChannel']),
            pathToRootCatalog: __DIR__ . '/../../../../',
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel('correctOrders', finalFailureStrategy: FinalFailureStrategy::RESEND),
            ]
        );

        $ecotone
            ->sendCommandWithRoutingKey('order.register', 'coffee')
            ->run('correctOrders', ExecutionPollingMetadata::createWithTestingSetup(failAtError: false))
        ;

        // First attempt fails, message is sent to error channel for delayed retry
        self::assertEquals(0, $ecotone->sendQueryWithRouting('getOrderAmount'));

        // Second attempt (first delayed retry) - still fails
        $ecotone
            ->run('correctOrders', ExecutionPollingMetadata::createWithTestingSetup(failAtError: false))
        ;

        self::assertEquals(0, $ecotone->sendQueryWithRouting('getOrderAmount'));

        // Third attempt (second delayed retry)
        $ecotone
            ->run('correctOrders', ExecutionPollingMetadata::createWithTestingSetup(failAtError: false))
        ;

        $this->assertSame(3, $ecotone->sendQueryWithRouting('getCallCount'));

        $this->assertSame(0, $ecotone->sendQueryWithRouting('getOrderAmount'));
        $ecotone
            ->run('correctOrders', ExecutionPollingMetadata::createWithTestingSetup(failAtError: false))
        ;
        $this->assertSame(1, $ecotone->sendQueryWithRouting('getOrderAmount'));
    }


    public function test_exception_handling_with_retries_without_dead_letter_uses_final_failure_strategy_with_ignore(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [OrderService::class],
            [new OrderService()],
            ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackagesExcept([ModulePackageList::ASYNCHRONOUS_PACKAGE]))
                ->withNamespaces(['Test\Ecotone\Messaging\Fixture\Handler\ErrorChannel']),
            pathToRootCatalog: __DIR__ . '/../../../../',
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel('correctOrders', finalFailureStrategy: FinalFailureStrategy::IGNORE),
            ]
        );

        $ecotone
            ->sendCommandWithRoutingKey('order.register', 'coffee')
            ->run('correctOrders', ExecutionPollingMetadata::createWithTestingSetup(failAtError: false))
        ;

        // First attempt fails, message is sent to error channel for delayed retry
        self::assertEquals(0, $ecotone->sendQueryWithRouting('getOrderAmount'));

        // Second attempt (first delayed retry) - still fails
        $ecotone
            ->run('correctOrders', ExecutionPollingMetadata::createWithTestingSetup(failAtError: false))
        ;

        self::assertEquals(0, $ecotone->sendQueryWithRouting('getOrderAmount'));

        // Third attempt (second delayed retry)
        $ecotone
            ->run('correctOrders', ExecutionPollingMetadata::createWithTestingSetup(failAtError: false))
        ;

        $this->assertSame(0, $ecotone->sendQueryWithRouting('getOrderAmount'));
        $ecotone
            ->run('correctOrders', ExecutionPollingMetadata::createWithTestingSetup(failAtError: false))
        ;
        $this->assertSame(0, $ecotone->sendQueryWithRouting('getOrderAmount'));
    }

    public function test_using_custom_channel_for_error_handling(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [OrderService::class],
            [new OrderService()],
            ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackagesExcept([ModulePackageList::ASYNCHRONOUS_PACKAGE]))
                ->withExtensionObjects([
                    ErrorHandlerConfiguration::create(
                        $errorChannelName = 'failureOrders',
                        RetryTemplateBuilder::exponentialBackoff(1, 1)
                            ->maxRetryAttempts(2)
                    ),
                ])
                ->withDefaultErrorChannel($errorChannelName),
            pathToRootCatalog: __DIR__ . '/../../../../',
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel('correctOrders', finalFailureStrategy: FinalFailureStrategy::IGNORE),
                SimpleMessageChannelBuilder::createQueueChannel($errorChannelName, finalFailureStrategy: FinalFailureStrategy::RESEND),
            ]
        );

        $ecotone
            ->sendCommandWithRoutingKey('order.register', 'coffee')
            ->run('correctOrders', ExecutionPollingMetadata::createWithTestingSetup(failAtError: false))
        ;

        // First attempt fails, message is sent to error channel for delayed retry
        self::assertEquals(0, $ecotone->sendQueryWithRouting('getOrderAmount'));

        // Second attempt (first delayed retry) - still fails
        $ecotone
            ->run($errorChannelName, ExecutionPollingMetadata::createWithTestingSetup(failAtError: false))
        ;

        self::assertEquals(0, $ecotone->sendQueryWithRouting('getOrderAmount'));

        // Third attempt (second delayed retry)
        $ecotone
            ->run($errorChannelName, ExecutionPollingMetadata::createWithTestingSetup(failAtError: false))
        ;

        $this->assertSame(3, $ecotone->sendQueryWithRouting('getCallCount'));

        $this->assertSame(0, $ecotone->sendQueryWithRouting('getOrderAmount'));
        $ecotone
            ->run($errorChannelName, ExecutionPollingMetadata::createWithTestingSetup(failAtError: true))
        ;
        $this->assertSame(1, $ecotone->sendQueryWithRouting('getOrderAmount'));
    }
}
