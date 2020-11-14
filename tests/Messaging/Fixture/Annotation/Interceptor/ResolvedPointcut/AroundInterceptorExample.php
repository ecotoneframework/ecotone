<?php


namespace Test\Ecotone\Messaging\Fixture\Annotation\Interceptor\ResolvedPointcut;


use Ecotone\Messaging\Annotation\Interceptor\Around;

class AroundInterceptorExample
{
    #[Around]
    public function withNoAttribute(\stdClass $class) : void
    {

    }

    #[Around]
    public function withSingleAttribute(AttributeOne $attributeOne) : void
    {

    }

    #[Around]
    public function withTwoOptionalAttributes(?AttributeOne $attributeOne, ?AttributeTwo $attributeTwo)
    {

    }

    #[Around]
    public function withTwoRequiredAttributes(AttributeOne $attributeOne, AttributeTwo $attributeTwo)
    {

    }

    #[Around]
    public function withUnionAttributes(AttributeOne|AttributeTwo $attributeOne)
    {

    }

    #[Around]
    public function withUnionAttributesAndRequiredAttribute(AttributeOne|AttributeTwo $attributeOne, AttributeThree $attributeThree)
    {

    }

    #[Around]
    public function withOptionalUnionAttributes(AttributeOne|AttributeTwo|null $attributeOne)
    {

    }

    #[Around]
    public function withUnionTypeOfAttributeAndNonAttributeClass(AttributeOne|\stdClass $attributeOne)
    {

    }

    #[Around]
    public function withOptionalAttributesAndRequired(?AttributeOne $attributeOne, AttributeTwo $attributeTwo, ?AttributeThree $attributeThree)
    {

    }
}