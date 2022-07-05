<?php
declare(strict_types=1);

namespace Tests\Ecotone\Messaging\Fixture\Annotation\ModuleConfiguration;

use Ecotone\Messaging\Attribute\ServiceContext;

class ExampleModuleExtensionObject
{
    #[ServiceContext]
    public function extensionObject() : \stdClass
    {
        return new \stdClass();
    }
}