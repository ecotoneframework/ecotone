<?php

declare(strict_types=1);

namespace Ecotone\Test\Flow\Fixture;

use Ecotone\Api\Attribute\EventTag;

/**
 * licence Apache-2.0
 */
final readonly class FlowStudentEnrolled
{
    public function __construct(
        #[EventTag('course')] public string $courseId,
        public string $studentId,
    ) {
    }
}
