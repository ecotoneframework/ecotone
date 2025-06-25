<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Scheduling;

use Ecotone\Messaging\Scheduling\CronIntegration\CronExpression;
use Ecotone\Messaging\Support\InvalidArgumentException;

/**
 * Class CronTrigger
 * @package Ecotone\Messaging\Scheduling
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class CronTrigger implements Trigger
{
    private ?string $cronExpression;

    private function __construct(string $cronExpression)
    {
        $this->initialize($cronExpression);
    }

    /**
     * @param string $cronExpression
     * @return CronTrigger
     * @throws InvalidArgumentException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public static function createWith(string $cronExpression): self
    {
        return new self($cronExpression);
    }

    /**
     * @inheritDoc
     */
    public function nextExecutionTime(EcotoneClockInterface $clock, TriggerContext $triggerContext): DatePoint
    {
        $cron = CronExpression::factory($this->cronExpression);

        if (! $triggerContext->lastActualExecutionTime() && $triggerContext->lastScheduledTime()) {
            return $triggerContext->lastScheduledTime();
        }
        if ($this->hasScheduledButNotYetExecuted($triggerContext)) {
            return $triggerContext->lastScheduledTime();
        }

        $dateTime = $clock->now();

        $nextExecutionTime = $cron->getNextRunDate($dateTime, 0, true, 'UTC');
        if ($nextExecutionTime < $dateTime) {
            $nextExecutionTime = $cron->getNextRunDate($dateTime, 1, true, 'UTC');
        }

        return DatePoint::createFromInterface($nextExecutionTime);
    }

    /**
     * @param string $cronExpression
     * @throws InvalidArgumentException
     * @throws \Ecotone\Messaging\MessagingException
     */
    private function initialize(string $cronExpression): void
    {
        if (! CronExpression::isValidExpression($cronExpression)) {
            throw InvalidArgumentException::create("Passed cron expression {$cronExpression} is not correct");
        }

        $this->cronExpression = $cronExpression;
    }

    /**
     * @param TriggerContext $triggerContext
     * @return bool
     */
    private function hasScheduledButNotYetExecuted(TriggerContext $triggerContext): bool
    {
        return
            ($triggerContext->lastActualExecutionTime() && $triggerContext->lastScheduledTime())
            &&
            ($triggerContext->lastActualExecutionTime() < $triggerContext->lastScheduledTime());
    }
}
