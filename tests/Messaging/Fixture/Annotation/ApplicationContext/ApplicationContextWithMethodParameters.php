<?php
declare(strict_types=1);

namespace Ecotone\Tests\Messaging\Fixture\Annotation\ApplicationContext;

use Ecotone\Messaging\Attribute\ServiceContext;

class ApplicationContextWithMethodParameters
{
    #[ServiceContext]
    public function someExtension(\stdClass $some)
    {
        return new \stdClass();
    }
}