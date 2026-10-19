<?php

declare(strict_types=1);

namespace Ecotone\Test\Flow\Fixture;

use Ecotone\Api\Attribute\Apply;
use Ecotone\Api\Attribute\DecisionModel;

/**
 * licence Apache-2.0
 */
#[DecisionModel]
final class FlowCourseCapacity
{
    private int $capacity = 0;

    private int $seatsTaken = 0;

    #[Apply]
    public function defined(FlowCourseDefined $event): void
    {
        $this->capacity = $event->capacity;
    }

    #[Apply]
    public function enrolled(FlowStudentEnrolled $event): void
    {
        $this->seatsTaken++;
    }

    public function hasFreeSeat(): bool
    {
        return $this->seatsTaken < $this->capacity;
    }
}
