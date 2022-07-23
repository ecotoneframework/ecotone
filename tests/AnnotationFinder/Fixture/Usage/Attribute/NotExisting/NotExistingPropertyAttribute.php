<?php

namespace Test\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\NotExisting;

class NotExistingPropertyAttribute
{
    #[Johny('bla')]
    private string $some;
}
