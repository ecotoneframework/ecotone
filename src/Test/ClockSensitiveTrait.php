<?php

/*
 * licence Apache-2.0
 */
declare(strict_types=1);

namespace Ecotone\Test;

use Ecotone\Messaging\Scheduling\Clock;
use Ecotone\Messaging\Scheduling\EcotoneClockInterface;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\BeforeClass;

trait ClockSensitiveTrait
{
    /**
     * @beforeClass
     *
     * @before
     *
     * @internal
     */
    #[Before]
    #[BeforeClass]
    public static function saveClockBeforeTest(bool $save = true): EcotoneClockInterface
    {
        static $originalClock;

        if ($save && $originalClock) {
            self::restoreClockAfterTest();
        }

        return $save ? $originalClock = Clock::get() : $originalClock;
    }

    /**
     * @after
     *
     * @internal
     */
    #[After]
    protected static function restoreClockAfterTest(): void
    {
        Clock::set(self::saveClockBeforeTest(false));
    }
}
