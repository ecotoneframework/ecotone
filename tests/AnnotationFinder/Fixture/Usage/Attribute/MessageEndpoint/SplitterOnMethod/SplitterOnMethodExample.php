<?php

declare(strict_types=1);

namespace Test\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\MessageEndpoint\SplitterOnMethod;

use Test\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\Annotation\SomeHandlerAnnotation;

/**
 * licence Apache-2.0
 */
class SplitterOnMethodExample
{
    #[SomeHandlerAnnotation]
    public function split(string $payload): array
    {
        return [];
    }
}
