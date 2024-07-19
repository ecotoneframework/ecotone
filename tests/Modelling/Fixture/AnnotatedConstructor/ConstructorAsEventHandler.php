<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\AnnotatedConstructor;

use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\EventHandler;
use stdClass;

#[Aggregate]
/**
 * licence Apache-2.0
 */
final class ConstructorAsEventHandler
{
    #[EventHandler(endpointId: 'commandHandler')]
    public function __construct(stdClass $event)
    {
    }
}
