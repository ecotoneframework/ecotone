<?php

declare(strict_types=1);

namespace Ecotone\Test\Flow\Fixture;

use Ecotone\Api\Attribute\EventTag;

/**
 * licence Apache-2.0
 */
final readonly class FlowCourseDefined
{
    public function __construct(
        #[EventTag('course')] public string $courseId,
        public int $capacity,
    ) {
    }
}
