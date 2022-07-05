<?php
declare(strict_types=1);

namespace Ecotone\Tests\Messaging\Fixture\Annotation\ApplicationContext;

use Ecotone\Messaging\Attribute\ServiceContext;

class ApplicationContextWithConstructorParameters
{
    public function __construct(\stdClass $some)
    {
    }

    #[ServiceContext]
    public function someExtension()
    {
        return new \stdClass();
    }
}