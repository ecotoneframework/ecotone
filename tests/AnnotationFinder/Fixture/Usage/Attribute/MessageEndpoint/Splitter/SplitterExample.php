<?php
declare(strict_types=1);

namespace Ecotone\Tests\AnnotationFinder\Fixture\Usage\Attribute\MessageEndpoint\Splitter;

use Ecotone\Tests\AnnotationFinder\Fixture\Usage\Attribute\Annotation\MessageEndpoint;
use Ecotone\Tests\AnnotationFinder\Fixture\Usage\Attribute\Annotation\SomeHandlerAnnotation;

#[MessageEndpoint]
class SplitterExample
{
    #[SomeHandlerAnnotation]
    public function split(string $payload) : array
    {
        return [];
    }
}