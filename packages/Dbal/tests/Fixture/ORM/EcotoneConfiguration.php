<?php declare(strict_types=1);

namespace Test\Ecotone\Dbal\Fixture\ORM;

use Ecotone\Dbal\Configuration\DbalConfiguration;
use Ecotone\Messaging\Attribute\ServiceContext;

class EcotoneConfiguration
{
    #[ServiceContext]
    public function getDbalConfiguration(): DbalConfiguration
    {
        return DbalConfiguration::createWithDefaults()
                ->withDoctrineORMRepositories(true);
    }
}