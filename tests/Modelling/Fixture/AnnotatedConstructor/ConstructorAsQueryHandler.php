<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\AnnotatedConstructor;

use Ecotone\Api\Attribute\Aggregate;
use Ecotone\Api\Attribute\Identifier;
use Ecotone\Api\Attribute\QueryHandler;

#[Aggregate]
/**
 * licence Apache-2.0
 */
final class ConstructorAsQueryHandler
{
    #[Identifier]
    private string $id;

    #[QueryHandler(routingKey: 'test')]
    public function __construct()
    {
    }
}
