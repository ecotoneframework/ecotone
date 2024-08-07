<?php

namespace Test\Ecotone\Messaging\Fixture\Service;

use Ecotone\Messaging\Attribute\ServiceActivator;

/**
 * Class StaticallyCalledService
 * @package Test\Ecotone\Messaging\Fixture\Service
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class StaticallyCalledService
{
    private function __construct()
    {
    }

    #[ServiceActivator('run')]
    public static function run(string $something): string
    {
        return $something;
    }
}
