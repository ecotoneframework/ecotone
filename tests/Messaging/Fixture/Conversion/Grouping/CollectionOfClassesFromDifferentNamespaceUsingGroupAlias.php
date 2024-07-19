<?php

namespace Test\Ecotone\Messaging\Fixture\Conversion\Grouping;

use Test\Ecotone\Messaging\Fixture\Conversion\Grouping\Details\{Description, ProductName as Product};

/**
 * licence Apache-2.0
 */
class CollectionOfClassesFromDifferentNamespaceUsingGroupAlias
{
    /**
     * @var Description[]
     */
    public array $productDescriptions;
    /**
     * @var Product[]
     */
    public array $productNames;
}
