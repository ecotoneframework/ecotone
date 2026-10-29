<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\QueryHandlerAggregate;

use Ecotone\Messaging\Attribute\BusinessMethod;
use Ecotone\Messaging\Message;
use Ecotone\Modelling\Attribute\Identifier;

interface StorageService
{
    #[BusinessMethod('storage.getSmallBoxes')]
    public function getSmallBoxes(#[Identifier] $storageId): Message;

    #[BusinessMethod('storage.getBoxes')]
    public function getBoxes(#[Identifier] $storageId): Message;
}
