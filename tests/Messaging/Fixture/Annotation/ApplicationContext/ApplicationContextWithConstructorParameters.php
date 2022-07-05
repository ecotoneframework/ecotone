<?php
declare(strict_types=1);

namespace Tests\Ecotone\Messaging\Fixture\Annotation\ApplicationContext;

use Ecotone\AnnotationFinder\Attribute\Environment;
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