<?php
declare(strict_types=1);

namespace Ecotone\Tests\Messaging\Fixture\Annotation\ModuleConfiguration;

use Ecotone\Messaging\Attribute\ServiceContext;

class ExampleModuleExtensionObject
{
    #[ServiceContext]
    public function extensionObject() : \stdClass
    {
        return new \stdClass();
    }
}