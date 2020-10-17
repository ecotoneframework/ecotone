<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\ApplicationContext;

use Ecotone\Messaging\Annotation\ApplicationContext;

class ApplicationContextWithMethodParameters
{
    #[ApplicationContext]
    public function someExtension(\stdClass $some)
    {
        return new \stdClass();
    }
}