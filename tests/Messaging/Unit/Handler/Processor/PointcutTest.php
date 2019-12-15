<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler\Processor;

use PHPUnit\Framework\TestCase;
use Ecotone\Messaging\Annotation\Interceptor\Around;
use Ecotone\Messaging\Annotation\Interceptor\MethodInterceptor;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Pointcut;
use Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor\AspectWithoutMethodInterceptorExample;
use Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor\BaseInterceptorExample;
use Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor\CallMultipleUnorderedArgumentsInvocationInterceptorExample;
use Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor\MethodInterceptorWithoutAspectExample;

/**
 * Class PointcutTest
 * @package Test\Ecotone\Messaging\Unit\Handler\Processor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class PointcutTest extends TestCase
{
    public function test_if_empty_point_cut_it_should_no_cut()
    {
        $this->assertFalse(Pointcut::createEmpty()->doesItCut(InterfaceToCall::create(CallMultipleUnorderedArgumentsInvocationInterceptorExample::class, "callMultipleUnorderedArgumentsInvocation"), []));
    }

    public function test_pointing_to_exact_class()
    {
        $this->itShouldCut(
            CallMultipleUnorderedArgumentsInvocationInterceptorExample::class,
            InterfaceToCall::create(CallMultipleUnorderedArgumentsInvocationInterceptorExample::class, "callMultipleUnorderedArgumentsInvocation")
        );
    }

    public function test_pointing_to_abstract_class()
    {
        $this->itShouldCut(
            BaseInterceptorExample::class,
            InterfaceToCall::create(CallMultipleUnorderedArgumentsInvocationInterceptorExample::class, "callMultipleUnorderedArgumentsInvocation")
        );
    }

    public function test_pointing_to_class_with_or()
    {
        $this->itShouldCut(
            \stdClass::class . "||" . CallMultipleUnorderedArgumentsInvocationInterceptorExample::class,
            InterfaceToCall::create(CallMultipleUnorderedArgumentsInvocationInterceptorExample::class, "callMultipleUnorderedArgumentsInvocation")
        );
    }

    public function test_pointing_to_exact_method_in_class()
    {
        $this->itShouldCut(
            CallMultipleUnorderedArgumentsInvocationInterceptorExample::class . "::callMultipleUnorderedArgumentsInvocation()",
            InterfaceToCall::create(CallMultipleUnorderedArgumentsInvocationInterceptorExample::class, "callMultipleUnorderedArgumentsInvocation")
        );
    }

    public function test_not_pointing_to_if_method_name_is_different()
    {
        $this->itShouldNotCut(
            CallMultipleUnorderedArgumentsInvocationInterceptorExample::class . "::notCall()",
            InterfaceToCall::create(CallMultipleUnorderedArgumentsInvocationInterceptorExample::class, "callMultipleUnorderedArgumentsInvocation")
        );
    }

    public function test_targeting_on_method_annotation()
    {
        $this->itShouldCut(
            "@(" . Around::class . ")",
            InterfaceToCall::create(AspectWithoutMethodInterceptorExample::class, "doSomething")
        );

        $this->itShouldNotCut(
            "@(" . MethodInterceptor::class . ")",
            InterfaceToCall::create(AspectWithoutMethodInterceptorExample::class, "doSomething")
        );
    }

    public function test_targeting_on_class_annotation()
    {
        $this->itShouldNotCut(
            "@(" . Around::class . ")",
            InterfaceToCall::create(MethodInterceptorWithoutAspectExample::class, "doSomething")
        );

        $this->itShouldCut(
            "@(" . MethodInterceptor::class . ")",
            InterfaceToCall::create(MethodInterceptorWithoutAspectExample::class, "doSomething")
        );
    }

    public function test_intercepting_namespace_suffix()
    {
        $this->itShouldCut(
            "Test\Ecotone\Messaging\Fixture\Handler\Processor\*",
            InterfaceToCall::create(MethodInterceptorWithoutAspectExample::class, "doSomething")
        );
    }

    public function test_intercepting_namespace_prefix()
    {
        $this->itShouldCut(
            "*\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor\MethodInterceptorWithoutAspectExample",
            InterfaceToCall::create(MethodInterceptorWithoutAspectExample::class, "doSomething")
        );
    }

    public function test_intercepting_namespace_in_the_middle()
    {
        $this->itShouldCut(
            "Test\Ecotone\Messaging\Fixture\*\Interceptor\MethodInterceptorWithoutAspectExample",
            InterfaceToCall::create(MethodInterceptorWithoutAspectExample::class, "doSomething")
        );
    }

    public function test_targeting_on_endpoint_annotations()
    {
        $this->assertTrue(Pointcut::createWith("@(" . \stdClass::class . ")")->doesItCut(
                InterfaceToCall::create(MethodInterceptorWithoutAspectExample::class, "doSomething"),
                [new \stdClass()]
            )
        );
    }


    /**
     * @param string $expression
     * @param InterfaceToCall $doesItCut
     * @throws \Ecotone\Messaging\Handler\TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    private function itShouldCut(string $expression, InterfaceToCall $doesItCut): void
    {
        $this->assertTrue(Pointcut::createWith($expression)->doesItCut($doesItCut, []));
    }

    /**
     * @param string $expression
     * @param InterfaceToCall $doesItCut
     * @throws \Ecotone\Messaging\Handler\TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    private function itShouldNotCut(string $expression, InterfaceToCall $doesItCut): void
    {
        $this->assertFalse(Pointcut::createWith($expression)->doesItCut($doesItCut, []));
    }
}