<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Scheduling;
use PHPUnit\Framework\TestCase;
use Ecotone\Messaging\Scheduling\PeriodicTrigger;
use Ecotone\Messaging\Scheduling\SimpleTriggerContext;
use Ecotone\Messaging\Scheduling\StubUTCClock;

/**
 * Class PeriodTriggerTest
 * @package Test\Ecotone\Messaging\Unit\Scheduling
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class PeriodTriggerTest extends TestCase
{
    public function test_trigger_at_current_time_when_first_schedule()
    {
        $periodTrigger = PeriodicTrigger::create(1, 0);

        $this->assertEquals(
            StubUTCClock::createEpochTimeFromDateTimeString("2017-01-01 00:00:00"),
            $periodTrigger->nextExecutionTime(
                StubUTCClock::createWithCurrentTime("2017-01-01 00:00:00"),
                SimpleTriggerContext::createEmpty()
            )
        );
    }

    public function test_scheduling_next_execution_time_after_last_being_done()
    {
        $periodTrigger = PeriodicTrigger::create(1000, 0);

        $this->assertEquals(
            StubUTCClock::createEpochTimeFromDateTimeString("2017-01-01 12:00:01"),
            $periodTrigger->nextExecutionTime(
                StubUTCClock::createWithCurrentTime("2017-01-01 00:00:00"),
                SimpleTriggerContext::createWith(null, StubUTCClock::createEpochTimeFromDateTimeString('2017-01-01 12:00:00'))
            )
        );
    }

    public function test_scheduling_next_execution_in_the_same_time_as_previous_if_not_yet_executed()
    {
        $periodTrigger = PeriodicTrigger::create(1000, 0);

        $this->assertEquals(
            StubUTCClock::createEpochTimeFromDateTimeString("2017-01-01 14:00:00"),
            $periodTrigger->nextExecutionTime(
                StubUTCClock::createWithCurrentTime("2017-01-01 00:00:00"),
                SimpleTriggerContext::createWith(StubUTCClock::createEpochTimeFromDateTimeString('2017-01-01 14:00:00'), null)
            )
        );
    }

    public function test_moving_to_next_rate_when_execution_equal_last_planned()
    {
        $periodTrigger = PeriodicTrigger::create(1000, 100);

        $this->assertEquals(
            StubUTCClock::createEpochTimeFromDateTimeString("2017-01-01 14:30:01"),
            $periodTrigger->nextExecutionTime(
                StubUTCClock::createWithCurrentTime("2017-01-01 12:00:00"),
                SimpleTriggerContext::createWith(StubUTCClock::createEpochTimeFromDateTimeString('2017-01-01 14:30:00'), StubUTCClock::createEpochTimeFromDateTimeString('2017-01-01 14:30:00'))
            )
        );
    }

    public function test_scheduling_with_initial_delay_if_first_plan()
    {
        $periodTrigger = PeriodicTrigger::create(1000, 10000);

        $this->assertEquals(
            StubUTCClock::createEpochTimeFromDateTimeString("2017-01-01 00:00:10"),
            $periodTrigger->nextExecutionTime(
                StubUTCClock::createWithCurrentTime("2017-01-01 00:00:00"),
                SimpleTriggerContext::createEmpty()
            )
        );
    }

    public function test_not_scheduling_new_date_if_last_was_not_finished_yet()
    {
        $periodTrigger = PeriodicTrigger::create(600, 10);

        $this->assertEquals(
            StubUTCClock::createEpochTimeFromDateTimeString("2017-01-01 00:30:00"),
            $periodTrigger->nextExecutionTime(
                StubUTCClock::createWithCurrentTime("2017-01-01 00:21:00"),
                SimpleTriggerContext::createWith(StubUTCClock::createEpochTimeFromDateTimeString('2017-01-01 00:30:00'), StubUTCClock::createEpochTimeFromDateTimeString('2017-01-01 00:20:00'))
            )
        );
    }
}