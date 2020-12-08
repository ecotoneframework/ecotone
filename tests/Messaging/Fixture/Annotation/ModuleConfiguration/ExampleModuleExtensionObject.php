<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\ModuleConfiguration;

use Ecotone\Messaging\Annotation\ServiceContext;

class ExampleModuleExtensionObject
{
    #[ServiceContext]
    public function extensionObject() : \stdClass
    {
        return new \stdClass();
    }
}