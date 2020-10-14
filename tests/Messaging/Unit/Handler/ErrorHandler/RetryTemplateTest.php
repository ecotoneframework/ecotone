<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler\ErrorHandler;

use Ecotone\Messaging\Handler\Recoverability\RetryTemplateBuilder;
use PHPUnit\Framework\TestCase;

class RetryTemplateTest extends TestCase
{
    public function test_calculating_fixed_back_off_with_max_attempts()
    {
        $retryTemplate = RetryTemplateBuilder::fixedBackOff(100)
                            ->maxRetryAttempts(3)
                            ->build();

        $this->assertEquals(0, $retryTemplate->calculateNextDelay(0));
        $this->assertEquals(100, $retryTemplate->calculateNextDelay(1));
        $this->assertEquals(100, $retryTemplate->calculateNextDelay(2));

        $this->assertTrue($retryTemplate->canBeCalledNextTime(1));
        $this->assertTrue($retryTemplate->canBeCalledNextTime(3));
        $this->assertFalse($retryTemplate->canBeCalledNextTime(4));
    }

    public function test_calculating_exponential_back_off()
    {
        $retryTemplate = RetryTemplateBuilder::exponentialBackoff(10, 2)
            ->build();

        $this->assertEquals(10, $retryTemplate->calculateNextDelay(1));
        $this->assertEquals(20, $retryTemplate->calculateNextDelay(2));
        $this->assertEquals(40, $retryTemplate->calculateNextDelay(3));
        $this->assertEquals(80, $retryTemplate->calculateNextDelay(4));
        $this->assertEquals(160, $retryTemplate->calculateNextDelay(5));
        $this->assertEquals(320, $retryTemplate->calculateNextDelay(6));
    }

    public function test_stopping_on_max_delay()
    {
        $retryTemplate = RetryTemplateBuilder::exponentialBackoffWithMaxDelay(10, 2, 80)
            ->build();

        $this->assertTrue($retryTemplate->canBeCalledNextTime(1));
        $this->assertTrue($retryTemplate->canBeCalledNextTime(4));
        $this->assertFalse($retryTemplate->canBeCalledNextTime(6));
    }
}