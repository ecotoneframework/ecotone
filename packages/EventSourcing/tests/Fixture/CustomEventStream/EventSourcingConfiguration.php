<?php

namespace Test\Ecotone\EventSourcing\Fixture\CustomEventStream;

use Ecotone\EventSourcing\FromProophMessageToArrayConverter;
use Ecotone\Messaging\Attribute\Parameter\ConfigurationVariable;
use Ecotone\Messaging\Attribute\ServiceContext;
use Prooph\EventStore\Pdo\PersistenceStrategy\MySqlSingleStreamStrategy;
use Prooph\EventStore\Pdo\PersistenceStrategy\PostgresSingleStreamStrategy;

class EventSourcingConfiguration
{
    #[ServiceContext]
    public function aggregateStreamStrategy(#[ConfigurationVariable] $isPostgres)
    {
        return \Ecotone\EventSourcing\EventSourcingConfiguration::createWithDefaults()
            ->withCustomPersistenceStrategy(
                $isPostgres
                    ? new PostgresSingleStreamStrategy(new FromProophMessageToArrayConverter())
                    : new MySqlSingleStreamStrategy(new FromProophMessageToArrayConverter())
            );
    }
}
