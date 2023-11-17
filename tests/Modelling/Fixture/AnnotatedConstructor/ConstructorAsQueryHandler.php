<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\AnnotatedConstructor;

use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\QueryHandler;

#[Aggregate]
final class ConstructorAsQueryHandler
{
    #[QueryHandler(routingKey: 'test')]
    public function __construct()
    {
    }
}
