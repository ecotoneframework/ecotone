<?php


namespace Test\Ecotone\Messaging\Fixture\Conversion\Grouping;

use Test\Ecotone\Messaging\Fixture\Conversion\Grouping\Details\{Description, ProductName as Product};

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