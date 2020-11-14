<?php


namespace Test\Ecotone\Messaging\Unit\Handler\Processor;


use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\Messaging\Fixture\Annotation\Interceptor\ResolvedPointcut\AroundInterceptorExample;
use Test\Ecotone\Messaging\Fixture\Annotation\Interceptor\ResolvedPointcut\AttributeOne;
use Test\Ecotone\Messaging\Fixture\Annotation\Interceptor\ResolvedPointcut\AttributeTwo;

class AroundInterceptorReferenceTest extends TestCase
{
    public function test_resolve_no_pointcut()
    {
        $interceptorClass = AroundInterceptorExample::class;
        $methodName = "withNoAttribute";
        $expectedPointcut = "";

        $this->assertEquals(
            AroundInterceptorReference::create($interceptorClass, AroundInterceptorExample::class, $methodName, 0, $expectedPointcut, []),
            AroundInterceptorReference::create($interceptorClass, AroundInterceptorExample::class, $methodName, 0, "", [])
        );
    }

    public function test_resolve_pointcut_for_single_attribute()
    {
        $interceptorClass = AroundInterceptorExample::class;
        $methodName = "withSingleAttribute";
        $expectedPointcut = AttributeOne::class;

        $this->assertEquals(
            AroundInterceptorReference::create($interceptorClass, AroundInterceptorExample::class, $methodName, 0, $expectedPointcut, []),
            AroundInterceptorReference::create($interceptorClass, AroundInterceptorExample::class, $methodName, 0, "", [])
        );
    }

    public function test_resolve_pointcut_for_two_optional_attributes()
    {
        $interceptorClass = AroundInterceptorExample::class;
        $methodName = "withTwoOptionalAttributes";
        $expectedPointcut = AttributeOne::class  . "||" . AttributeTwo::class;

        $this->assertEquals(
            AroundInterceptorReference::create($interceptorClass, AroundInterceptorExample::class, $methodName, 0, $expectedPointcut, []),
            AroundInterceptorReference::create($interceptorClass, AroundInterceptorExample::class, $methodName, 0, "", [])
        );
    }
}