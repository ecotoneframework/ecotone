<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\ModuleConfiguration;

use Ecotone\Messaging\Attribute\ServiceContext;
use stdClass;

/**
 * licence Apache-2.0
 */
class ExampleModuleExtensionObject
{
    #[ServiceContext]
    public function extensionObject(): stdClass
    {
        return new stdClass();
    }
}
