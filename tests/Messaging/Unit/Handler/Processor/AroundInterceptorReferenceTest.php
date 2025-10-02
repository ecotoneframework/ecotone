<?php

namespace Test\Ecotone\Messaging\Unit\Handler\Processor;

use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\AllHeadersBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\HeaderBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\PayloadBuilder;
use Ecotone\Messaging\Support\InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\Messaging\Fixture\Annotation\Interceptor\ResolvedPointcut\AroundInterceptorExample;
use Test\Ecotone\Messaging\Fixture\Annotation\Interceptor\ResolvedPointcut\AttributeOne;
use Test\Ecotone\Messaging\Fixture\Annotation\Interceptor\ResolvedPointcut\AttributeThree;
use Test\Ecotone\Messaging\Fixture\Annotation\Interceptor\ResolvedPointcut\AttributeTwo;

/**
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
class AroundInterceptorReferenceTest extends TestCase
{
    public function test_resolve_no_pointcut()
    {
        $interceptorClass = AroundInterceptorExample::class;
        $methodName = 'withNoAttribute';
        $expectedPointcut = '';

        $this->assertEquals(
            AroundInterceptorBuilder::create(AroundInterceptorExample::class, InterfaceToCall::create($interceptorClass, $methodName), 0, $expectedPointcut, []),
            AroundInterceptorBuilder::create(AroundInterceptorExample::class, InterfaceToCall::create($interceptorClass, $methodName), 0, '', [])
        );
    }

    public function test_resolve_pointcut_for_single_attribute()
    {
        $interceptorClass = AroundInterceptorExample::class;
        $methodName = 'withSingleAttribute';
        $expectedPointcut = '(' . AttributeOne::class . ')';

        $this->assertEquals(
            AroundInterceptorBuilder::create(AroundInterceptorExample::class, InterfaceToCall::create($interceptorClass, $methodName), 0, $expectedPointcut, []),
            AroundInterceptorBuilder::create(AroundInterceptorExample::class, InterfaceToCall::create($interceptorClass, $methodName), 0, '', [])
        );
    }

    public function test_resolve_pointcut_for_two_optional_attributes()
    {
        $interceptorClass = AroundInterceptorExample::class;
        $methodName = 'withTwoOptionalAttributes';
        $expectedPointcut = '(' . AttributeOne::class  . '||' . AttributeTwo::class . ')';

        $this->assertEquals(
            AroundInterceptorBuilder::create(AroundInterceptorExample::class, InterfaceToCall::create($interceptorClass, $methodName), 0, $expectedPointcut, []),
            AroundInterceptorBuilder::create(AroundInterceptorExample::class, InterfaceToCall::create($interceptorClass, $methodName), 0, '', [])
        );
    }

    public function test_resolve_pointcut_for_two_required_attributes()
    {
        $interceptorClass = AroundInterceptorExample::class;
        $methodName = 'withTwoRequiredAttributes';
        $expectedPointcut = '(' . AttributeOne::class  . ')&&(' . AttributeTwo::class . ')';

        $this->assertEquals(
            AroundInterceptorBuilder::create(AroundInterceptorExample::class, InterfaceToCall::create($interceptorClass, $methodName), 0, $expectedPointcut, []),
            AroundInterceptorBuilder::create(AroundInterceptorExample::class, InterfaceToCall::create($interceptorClass, $methodName), 0, '', [])
        );
    }

    public function test_group_optional_attributes_together()
    {
        $interceptorClass = AroundInterceptorExample::class;
        $methodName = 'withOptionalAttributesAndRequired';
        $expectedPointcut = '(' . AttributeOne::class  . '||' . AttributeThree::class . ')&&(' . AttributeTwo::class . ')';

        $this->assertEquals(
            AroundInterceptorBuilder::create(AroundInterceptorExample::class, InterfaceToCall::create($interceptorClass, $methodName), 0, $expectedPointcut, []),
            AroundInterceptorBuilder::create(AroundInterceptorExample::class, InterfaceToCall::create($interceptorClass, $methodName), 0, '', [])
        );
    }

    public function test_throwing_exception_if_optional_union_type_given()
    {
        $interceptorClass = AroundInterceptorExample::class;
        $methodName = 'withOptionalUnionAttributesAndRequiredAttribute';

        $this->expectException(InvalidArgumentException::class);

        AroundInterceptorBuilder::create(AroundInterceptorExample::class, InterfaceToCall::create($interceptorClass, $methodName), 0, '', []);
    }

    public function test_throwing_exception_if_attribute_joined_with_non_attribute()
    {
        $interceptorClass = AroundInterceptorExample::class;
        $methodName = 'withUnionTypeOfAttributeAndNonAttributeClass';

        $this->expectException(InvalidArgumentException::class);

        AroundInterceptorBuilder::create(AroundInterceptorExample::class, InterfaceToCall::create($interceptorClass, $methodName), 0, '', []);
    }

    public function test_ignoring_non_class_parameters()
    {
        $interceptorClass = AroundInterceptorExample::class;
        $methodName = 'withNonClassParameters';
        $expectedPointcut = '(' . AttributeOne::class . ')';

        $this->assertEquals(
            AroundInterceptorBuilder::create(AroundInterceptorExample::class, InterfaceToCall::create($interceptorClass, $methodName), 0, $expectedPointcut, []),
            AroundInterceptorBuilder::create(AroundInterceptorExample::class, InterfaceToCall::create($interceptorClass, $methodName), 0, '', [])
        );
    }

    public function test_ignoring_non_attribute_parameters()
    {
        $interceptorClass = AroundInterceptorExample::class;
        $methodName = 'withNonAnnotationClass';
        $expectedPointcut = '(' . AttributeOne::class . ')';

        $this->assertEquals(
            AroundInterceptorBuilder::create(AroundInterceptorExample::class, InterfaceToCall::create($interceptorClass, $methodName), 0, $expectedPointcut, []),
            AroundInterceptorBuilder::create(AroundInterceptorExample::class, InterfaceToCall::create($interceptorClass, $methodName), 0, '', [])
        );
    }

    public function test_ignoring_parameter_converters()
    {
        $interceptorClass = AroundInterceptorExample::class;
        $methodName = 'withParameterConverters';
        $expectedPointcut = '(' . AttributeOne::class . ')';

        $this->assertEquals(
            AroundInterceptorBuilder::create(AroundInterceptorExample::class, InterfaceToCall::create($interceptorClass, $methodName), 0, $expectedPointcut, [
                PayloadBuilder::create('payload'),
                HeaderBuilder::create('class', 'token'),
                AllHeadersBuilder::createWith('headers'),
            ]),
            AroundInterceptorBuilder::create(AroundInterceptorExample::class, InterfaceToCall::create($interceptorClass, $methodName), 0, '', [
                PayloadBuilder::create('payload'),
                HeaderBuilder::create('class', 'token'),
                AllHeadersBuilder::createWith('headers'),
            ])
        );
    }
}
