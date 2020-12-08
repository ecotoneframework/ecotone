<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\ApplicationContext;

use Ecotone\Messaging\Annotation\ServiceContext;

class ApplicationContextWithMethodParameters
{
    #[ServiceContext]
    public function someExtension(\stdClass $some)
    {
        return new \stdClass();
    }
}