<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\EventRouting;

use Ecotone\Modelling\Attribute\QueryHandler;

/**
 * licence Apache-2.0
 */
final class ServiceWithPrivateQueryHandler
{
    #[QueryHandler('getOrder')]
    private function getOrder(): string
    {
        return 'order';
    }
}
