<?php

namespace Test\Ecotone\Dbal\Fixture\InMemoryDocumentStore;

use Ecotone\Dbal\Configuration\DbalConfiguration;
use Ecotone\Messaging\Attribute\ServiceContext;

final class InMemoryDbalConfiguration
{
    #[ServiceContext]
    public function configuration()
    {
        return DbalConfiguration::createWithDefaults()
                    ->withDocumentStore(inMemoryDocumentStore: true);
    }
}
