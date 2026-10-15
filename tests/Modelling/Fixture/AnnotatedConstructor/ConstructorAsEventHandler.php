<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\AnnotatedConstructor;

use Ecotone\Api\Attribute\Aggregate;
use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\Attribute\Identifier;
use stdClass;

#[Aggregate]
/**
 * licence Apache-2.0
 */
final class ConstructorAsEventHandler
{
    #[Identifier]
    private string $id;

    #[EventHandler(endpointId: 'commandHandler')]
    public function __construct(stdClass $event)
    {
    }
}
