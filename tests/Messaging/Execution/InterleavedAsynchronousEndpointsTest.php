<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Execution;

use Attribute;
use Ecotone\Api\Attribute\Around;
use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\AsynchronousEndpointAttribute;
use Ecotone\Api\Attribute\AsynchronousRunningEndpoint;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\QueryHandler;
use Ecotone\Api\ExtensionObject\ExecutionPollingMetadata;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Interceptor\MethodInvocation;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Test\LicenceTesting;
use Fiber;
use PHPUnit\Framework\TestCase;

/**
 * licence Apache-2.0
 * @internal
 */
final class InterleavedAsynchronousEndpointsTest extends TestCase
{
    public function test_interceptor_of_a_resumed_execution_receives_the_attribute_of_its_own_asynchronous_endpoint(): void
    {
        $orders = new class () {
            #[Asynchronous('orders', asynchronousExecution: [new TenantOfEndpoint('tenant-of-orders')])]
            #[CommandHandler('order.place', endpointId: 'order.place.endpoint')]
            public function place(string $command): void
            {
            }

            #[Asynchronous('invoices', asynchronousExecution: [new TenantOfEndpoint('tenant-of-invoices')])]
            #[CommandHandler('invoice.issue', endpointId: 'invoice.issue.endpoint')]
            public function issue(string $command): void
            {
            }
        };
        $interceptors = new class () {
            private bool $suspendedOnce = false;
            private array $tenantsSeen = [];

            #[Around(precedence: -10, pointcut: AsynchronousRunningEndpoint::class)]
            public function suspendFirstExecution(MethodInvocation $methodInvocation): mixed
            {
                if (! $this->suspendedOnce) {
                    $this->suspendedOnce = true;
                    Fiber::suspend();
                }

                return $methodInvocation->proceed();
            }

            #[Around(precedence: 10, pointcut: AsynchronousRunningEndpoint::class)]
            public function recordTenant(MethodInvocation $methodInvocation, ?TenantOfEndpoint $tenant = null): mixed
            {
                $this->tenantsSeen[] = $tenant?->name;

                return $methodInvocation->proceed();
            }

            #[QueryHandler('tenantsSeen')]
            public function tenantsSeen(): array
            {
                return $this->tenantsSeen;
            }
        };
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [$orders::class, $interceptors::class],
            [$orders, $interceptors],
            extensionObjects: [
                SimpleMessageChannelBuilder::createQueueChannel('orders'),
                SimpleMessageChannelBuilder::createQueueChannel('invoices'),
            ],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
        $ecotone->sendCommandWithRouting('order.place', 'order');
        $ecotone->sendCommandWithRouting('invoice.issue', 'invoice');

        $suspended = new Fiber(fn () => $ecotone->run('orders', ExecutionPollingMetadata::createWithTestingSetup()));
        $interleaved = new Fiber(fn () => $ecotone->run('invoices', ExecutionPollingMetadata::createWithTestingSetup()));
        $suspended->start();
        $interleaved->start();
        $suspended->resume();

        $this->assertTrue($interleaved->isTerminated());
        $this->assertTrue($suspended->isTerminated());
        $this->assertSame(
            ['tenant-of-invoices', 'tenant-of-orders'],
            $ecotone->sendQueryWithRouting('tenantsSeen')
        );
    }
}

#[Attribute]
final class TenantOfEndpoint implements AsynchronousEndpointAttribute
{
    public function __construct(public string $name)
    {
    }
}
