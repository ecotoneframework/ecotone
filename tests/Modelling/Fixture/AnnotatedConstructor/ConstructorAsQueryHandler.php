<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\AnnotatedConstructor;

use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\Identifier;
use Ecotone\Modelling\Attribute\QueryHandler;

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
