<?php

namespace Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate;

use Ecotone\Api\Attribute\Aggregate;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\IdentifierMethod;
use stdClass;
use Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate\NoIdDefinedAfterCallingFactory\CreateNoIdDefinedAggregate;

#[Aggregate]
/**
 * licence Apache-2.0
 */
class PublicIdentifierGetMethodWithParameters
{
    private $internalId;

    #[CommandHandler]
    public static function create(CreateNoIdDefinedAggregate $command): array
    {
        return [new stdClass()];
    }

    #[IdentifierMethod('id')]
    public function getId(stdClass $param)
    {
        return $this->internalId;
    }
}
