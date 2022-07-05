<?php
declare(strict_types=1);

namespace Tests\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\MessageEndpoint\Splitter;

use Tests\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\Annotation\MessageEndpoint;
use Tests\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\Annotation\SomeHandlerAnnotation;

#[MessageEndpoint]
class SplitterExample
{
    #[SomeHandlerAnnotation]
    public function split(string $payload) : array
    {
        return [];
    }
}