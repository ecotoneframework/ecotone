<?php
declare(strict_types=1);

namespace Ecotone\Tests\AnnotationFinder\Fixture\Usage\Attribute\MessageEndpoint\SplitterOnMethod;

use Ecotone\Tests\AnnotationFinder\Fixture\Usage\Attribute\Annotation\SomeHandlerAnnotation;

class SplitterOnMethodExample
{
    #[SomeHandlerAnnotation]
    public function split(string $payload) : array
    {
        return [];
    }
}