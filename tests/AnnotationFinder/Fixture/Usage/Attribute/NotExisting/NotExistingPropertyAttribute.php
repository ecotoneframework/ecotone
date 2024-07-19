<?php

namespace Test\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\NotExisting;

/**
 * licence Apache-2.0
 */
class NotExistingPropertyAttribute
{
    #[Johny('bla')]
    private string $some;
}
