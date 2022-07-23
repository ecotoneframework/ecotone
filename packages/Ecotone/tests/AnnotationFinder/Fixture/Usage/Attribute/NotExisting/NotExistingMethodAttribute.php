<?php

namespace Test\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\NotExisting;

use Test\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\Annotation\SomeGatewayExample;

class NotExistingMethodAttribute
{
    #[SomeGatewayExample]
    #[Johny('bla')]
    public function test()
    {
    }
}
