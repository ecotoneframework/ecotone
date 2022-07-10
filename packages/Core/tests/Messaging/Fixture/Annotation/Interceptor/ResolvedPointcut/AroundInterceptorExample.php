<?php


namespace Test\Ecotone\Messaging\Fixture\Annotation\Interceptor\ResolvedPointcut;


use Ecotone\Messaging\Attribute\Interceptor\Around;
use Ecotone\Messaging\Attribute\Parameter\Header;
use Ecotone\Messaging\Attribute\Parameter\Headers;
use Ecotone\Messaging\Attribute\Parameter\Payload;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;
use Ecotone\Modelling\CommandBus;
use Ecotone\Modelling\EventBus;

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

    #[Around]
    public function withNonClassParameters(AttributeOne $attributeOne, string $payload, array $objects, CommandBus|EventBus $commandBus)
    {

    }

    #[Around]
    public function withNonAnnotationClass(AttributeOne $attributeOne, \stdClass $class)
    {

    }

    #[Around]
    public function withParameterConverters(AttributeOne $attributeOne, #[Payload] string $payload, #[Header("token")] \stdClass $class, #[Headers] array $headers)
    {

    }
}