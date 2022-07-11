<?php

namespace Test\Ecotone\Laravel;

use Ecotone\Laravel\CombinedLogger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @internal
 */
class CombinedLoggerTest extends TestCase
{
    public function test_it_proxies_logs()
    {
        $applicationLogger = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $consoleLogger = $this->getMockBuilder(LoggerInterface::class)->getMock();

        $applicationLogger
            ->expects($this->once())
            ->method('log')
            ->with(1, 'Test log', []);

        $consoleLogger
            ->expects($this->once())
            ->method('log')
            ->with(1, 'Test log', []);

        $combinedLogger = new CombinedLogger($applicationLogger, $consoleLogger);

        $combinedLogger->log(1, 'Test log', []);
    }
}
