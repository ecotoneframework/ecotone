<?php

declare(strict_types=1);

namespace Ecotone\Test\Flow\Fixture;

/**
 * licence Apache-2.0
 */
final readonly class FlowEnrolStudent
{
    public function __construct(
        public string $courseId,
        public string $studentId,
    ) {
    }
}
