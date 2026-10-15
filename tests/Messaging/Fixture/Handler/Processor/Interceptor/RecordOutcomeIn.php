<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor;

use Attribute;

#[Attribute]
/**
 * licence Apache-2.0
 */
final class RecordOutcomeIn
{
    public function __construct(public readonly string $recorderReferenceName)
    {
    }
}
