<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\ModuleConfiguration;

use Ecotone\Messaging\Annotation\ApplicationContext;

class ExampleModuleExtensionObject
{
    #[ApplicationContext]
    public function extensionObject() : \stdClass
    {
        return new \stdClass();
    }
}