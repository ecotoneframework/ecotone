<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\Messaging\Unit\Handler\Processor;

use PHPUnit\Framework\TestCase;
use SimplyCodedSoftware\Messaging\Annotation\Interceptor\Around;
use SimplyCodedSoftware\Messaging\Annotation\Interceptor\MethodInterceptor;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\Pointcut;
use Test\SimplyCodedSoftware\Messaging\Fixture\Handler\Processor\Interceptor\AspectWithoutMethodInterceptorExample;
use Test\SimplyCodedSoftware\Messaging\Fixture\Handler\Processor\Interceptor\CallMultipleUnorderedArgumentsInvocationInterceptorExample;
use Test\SimplyCodedSoftware\Messaging\Fixture\Handler\Processor\Interceptor\MethodInterceptorWithoutAspectExample;

/**
 * Class PointcutTest
 * @package Test\SimplyCodedSoftware\Messaging\Unit\Handler\Processor
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
     * @throws \SimplyCodedSoftware\Messaging\Handler\TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    private function itShouldCut(string $expression, InterfaceToCall $doesItCut): void
    {
        $this->assertTrue(Pointcut::createWith($expression)->doesItCut($doesItCut, []));
    }

    /**
     * @param string $expression
     * @param InterfaceToCall $doesItCut
     * @throws \SimplyCodedSoftware\Messaging\Handler\TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    private function itShouldNotCut(string $expression, InterfaceToCall $doesItCut): void
    {
        $this->assertFalse(Pointcut::createWith($expression)->doesItCut($doesItCut, []));
    }
}