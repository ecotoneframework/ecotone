<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\AnnotatedConstructor;

use Ecotone\Api\Attribute\Aggregate;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\Identifier;

#[Aggregate]
/**
 * licence Apache-2.0
 */
final class ConstructorAsCommandHandler
{
    #[Identifier]
    private string $id;

    #[CommandHandler(routingKey: 'test')]
    public function __construct()
    {
    }
}
