<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Endpoint\PollingConsumer;

use Ecotone\Lite\EcotoneLite;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Messaging\Endpoint\ExecutionPollingMetadata;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\Messaging\Fixture\Poller\ExpressionPollerExample;
use Test\Ecotone\Messaging\Fixture\Poller\TimerService;

/**
 * licence Apache-2.0
 * @internal
 */
final class PollerExpressionTest extends TestCase
{
    public function test_poller_with_fixed_rate_expression()
    {
        $timerService = new TimerService();
        $pollerExample = new ExpressionPollerExample();

        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [ExpressionPollerExample::class],
            [
                'timerService' => $timerService,
                $pollerExample,
            ],
            ServiceConfiguration::createWithDefaults()
        );

        // Run the consumer - this will evaluate the expression and create the trigger
        // The expression "reference('timerService').getFixedRate()" should return 2000
        $ecotoneLite->run('expression_poller_endpoint', ExecutionPollingMetadata::createWithDefaults()->withHandledMessageLimit(1));

        // Verify that the poller ran and processed a message
        $this->assertEquals(['fixed_rate_message'], $pollerExample->getProcessedMessages());
    }

    public function test_poller_with_cron_expression()
    {
        $timerService = new TimerService();
        $pollerExample = new ExpressionPollerExample();

        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [ExpressionPollerExample::class],
            [
                'timerService' => $timerService,
                $pollerExample,
            ],
            ServiceConfiguration::createWithDefaults()
        );

        $ecotoneLite->run('expression_poller_endpoint', ExecutionPollingMetadata::createWithTestingSetup(1, 100));

        $this->assertEquals(['fixed_rate_message'], $pollerExample->getProcessedMessages());
    }
}
