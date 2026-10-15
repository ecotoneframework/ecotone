<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\QueryHandlerAggregate;

use Ecotone\Api\Attribute\BusinessMethod;
use Ecotone\Api\Attribute\Identifier;
use Ecotone\Api\Messaging\Message;

interface StorageService
{
    #[BusinessMethod('storage.getSmallBoxes')]
    public function getSmallBoxes(#[Identifier] $storageId): Message;

    #[BusinessMethod('storage.getBoxes')]
    public function getBoxes(#[Identifier] $storageId): Message;
}
