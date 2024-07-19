<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\AnnotatedConstructor;

use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\CommandHandler;

#[Aggregate]
/**
 * licence Apache-2.0
 */
final class ConstructorAsCommandHandler
{
    #[CommandHandler(routingKey: 'test')]
    public function __construct()
    {
    }
}
