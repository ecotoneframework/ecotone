<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\AnnotatedConstructor;

use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\CommandHandler;

#[Aggregate]
final class ConstructorAsCommandHandler
{
    #[CommandHandler(routingKey: 'test')]
    public function __construct()
    {
    }
}
