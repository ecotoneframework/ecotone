<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler\Processor;

use Ecotone\Messaging\Attribute\AsynchronousRunningEndpoint;
use Ecotone\Messaging\Attribute\ClassReference;
use Ecotone\Messaging\Attribute\Interceptor\Around;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Pointcut;
use PHPUnit\Framework\TestCase;
use stdClass;
use Test\Ecotone\Messaging\Fixture\Annotation\Interceptor\ResolvedPointcut\AttributeOne;
use Test\Ecotone\Messaging\Fixture\Annotation\Interceptor\ResolvedPointcut\AttributeTwo;
use Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor\AspectWithoutMethodInterceptorExample;
use Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor\BaseInterceptorExample;
use Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor\CallMultipleUnorderedArgumentsInvocationInterceptorExample;
use Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor\MethodInterceptorWithoutAspectExample;

/**
 * Class PointcutTest
 * @package Test\Ecotone\Messaging\Unit\Handler\Processor
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 *
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
class PointcutTest extends TestCase
{
    public function test_if_empty_point_cut_it_should_no_cut()
    {
        $this->assertFalse(Pointcut::createEmpty()->doesItCut(InterfaceToCall::create(CallMultipleUnorderedArgumentsInvocationInterceptorExample::class, 'callMultipleUnorderedArgumentsInvocation'), [], InterfaceToCallRegistry::createEmpty()));
    }

    public function test_pointing_to_exact_class()
    {
        $this->itShouldCut(
            CallMultipleUnorderedArgumentsInvocationInterceptorExample::class,
            InterfaceToCall::create(CallMultipleUnorderedArgumentsInvocationInterceptorExample::class, 'callMultipleUnorderedArgumentsInvocation')
        );
    }

    public function test_pointing_to_abstract_class()
    {
        $this->itShouldCut(
            BaseInterceptorExample::class,
            InterfaceToCall::create(CallMultipleUnorderedArgumentsInvocationInterceptorExample::class, 'callMultipleUnorderedArgumentsInvocation')
        );
    }

    public function test_pointing_to_class_with_or()
    {
        $this->itShouldCut(
            stdClass::class . '||' . CallMultipleUnorderedArgumentsInvocationInterceptorExample::class . '||' . stdClass::class,
            InterfaceToCall::create(CallMultipleUnorderedArgumentsInvocationInterceptorExample::class, 'callMultipleUnorderedArgumentsInvocation')
        );
    }

    public function test_pointing_to_exact_method_in_class()
    {
        $this->itShouldCut(
            CallMultipleUnorderedArgumentsInvocationInterceptorExample::class . '::callMultipleUnorderedArgumentsInvocation',
            InterfaceToCall::create(CallMultipleUnorderedArgumentsInvocationInterceptorExample::class, 'callMultipleUnorderedArgumentsInvocation')
        );
    }

    public function test_not_pointing_to_if_method_name_is_different()
    {
        $this->itShouldNotCut(
            CallMultipleUnorderedArgumentsInvocationInterceptorExample::class . '::notCall()',
            InterfaceToCall::create(CallMultipleUnorderedArgumentsInvocationInterceptorExample::class, 'callMultipleUnorderedArgumentsInvocation')
        );
    }

    public function test_targeting_on_method_annotation()
    {
        $this->itShouldCut(
            Around::class,
            InterfaceToCall::create(AspectWithoutMethodInterceptorExample::class, 'doSomething')
        );

        $this->itShouldNotCut(
            ClassReference::class,
            InterfaceToCall::create(AspectWithoutMethodInterceptorExample::class, 'doSomething')
        );
    }

    public function test_targeting_on_class_annotation()
    {
        $this->itShouldNotCut(
            Around::class,
            InterfaceToCall::create(MethodInterceptorWithoutAspectExample::class, 'doSomething')
        );

        $this->itShouldCut(
            ClassReference::class,
            InterfaceToCall::create(MethodInterceptorWithoutAspectExample::class, 'doSomething')
        );
    }

    public function test_intercepting_namespace_suffix()
    {
        $this->itShouldCut(
            "Test\Ecotone\Messaging\Fixture\Handler\Processor\*",
            InterfaceToCall::create(MethodInterceptorWithoutAspectExample::class, 'doSomething')
        );
    }

    public function test_intercepting_namespace_prefix()
    {
        $this->itShouldCut(
            "*\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor\MethodInterceptorWithoutAspectExample",
            InterfaceToCall::create(MethodInterceptorWithoutAspectExample::class, 'doSomething')
        );
    }

    public function test_intercepting_namespace_in_the_middle()
    {
        $this->itShouldCut(
            "Test\Ecotone\Messaging\Fixture\*\Interceptor\MethodInterceptorWithoutAspectExample",
            InterfaceToCall::create(MethodInterceptorWithoutAspectExample::class, 'doSomething')
        );
    }

    public function test_cutting_with__an_d_pointcut()
    {
        $this->assertTrue(
            Pointcut::createWith(AttributeOne::class . '&&' . AttributeTwo::class)->doesItCut(
                InterfaceToCall::create(MethodInterceptorWithoutAspectExample::class, 'doSomething'),
                [new AttributeOne(), new AttributeTwo()],
                InterfaceToCallRegistry::createEmpty()
            )
        );
    }

    public function test_not_cutting_with__an_d_pointcut()
    {
        $this->assertFalse(
            Pointcut::createWith(AttributeOne::class . '&&' . AttributeTwo::class)->doesItCut(
                InterfaceToCall::create(MethodInterceptorWithoutAspectExample::class, 'doSomething'),
                [new AttributeTwo()],
                InterfaceToCallRegistry::createEmpty()
            )
        );
    }

    public function test_cutting_with_single_bracket()
    {
        $this->assertTrue(
            Pointcut::createWith('(' . AttributeOne::class . ')')->doesItCut(
                InterfaceToCall::create(MethodInterceptorWithoutAspectExample::class, 'doSomething'),
                [new AttributeOne()],
                InterfaceToCallRegistry::createEmpty()
            )
        );
    }

    public function test_cutting_with__o_r_and_brackets()
    {
        $this->assertTrue(
            Pointcut::createWith('(' . AttributeOne::class . ')||(' . AttributeTwo::class . ')')->doesItCut(
                InterfaceToCall::create(MethodInterceptorWithoutAspectExample::class, 'doSomething'),
                [new AttributeTwo()],
                InterfaceToCallRegistry::createEmpty()
            )
        );
    }

    public function test_not_cutting_with__an_d_and_brackets()
    {
        $this->assertFalse(
            Pointcut::createWith('(' . AttributeOne::class . ')&&(' . AttributeTwo::class . ')')->doesItCut(
                InterfaceToCall::create(MethodInterceptorWithoutAspectExample::class, 'doSomething'),
                [new AttributeTwo()],
                InterfaceToCallRegistry::createEmpty()
            )
        );
    }

    public function test_cutting_with__an_d_and_brackets()
    {
        $this->assertTrue(
            Pointcut::createWith('(' . AttributeOne::class . ')&&(' . AttributeTwo::class . ')')->doesItCut(
                InterfaceToCall::create(MethodInterceptorWithoutAspectExample::class, 'doSomething'),
                [new AttributeOne(), new AttributeTwo()],
                InterfaceToCallRegistry::createEmpty()
            )
        );
    }

    public function TODOtest_throwing_exception_on_brackets_inside_brackets()
    {
        $this->expectException(Pointcut\IncorrectPointcutException::class);

        Pointcut::createWith("((\stdClass))");
    }

    public function test_throwing_exception_when_no_expression_given_between_brackets()
    {
        $this->expectException(Pointcut\IncorrectPointcutException::class);

        Pointcut::createWith("(\stdClass)(\stdClass)");
    }

    public function test_targeting_on_endpoint_annotations()
    {
        $this->assertTrue(
            Pointcut::createWith(AsynchronousRunningEndpoint::class)->doesItCut(
                InterfaceToCall::create(MethodInterceptorWithoutAspectExample::class, 'doSomething'),
                [new AsynchronousRunningEndpoint('some')],
                InterfaceToCallRegistry::createEmpty()
            )
        );
    }

    public function test_simple_negation_should_not_cut_when_annotation_present()
    {
        $this->assertFalse(
            Pointcut::createWith('not(' . AttributeOne::class . ')')->doesItCut(
                InterfaceToCall::create(MethodInterceptorWithoutAspectExample::class, 'doSomething'),
                [new AttributeOne()],
                InterfaceToCallRegistry::createEmpty()
            )
        );
    }

    public function test_simple_negation_should_cut_when_annotation_not_present()
    {
        $this->assertTrue(
            Pointcut::createWith('not(' . AttributeOne::class . ')')->doesItCut(
                InterfaceToCall::create(MethodInterceptorWithoutAspectExample::class, 'doSomething'),
                [new AttributeTwo()],
                InterfaceToCallRegistry::createEmpty()
            )
        );
    }

    public function test_double_negation_should_work_like_original()
    {
        $this->assertTrue(
            Pointcut::createWith('not(not(' . AttributeOne::class . '))')->doesItCut(
                InterfaceToCall::create(MethodInterceptorWithoutAspectExample::class, 'doSomething'),
                [new AttributeOne()],
                InterfaceToCallRegistry::createEmpty()
            )
        );

        $this->assertFalse(
            Pointcut::createWith('not(not(' . AttributeOne::class . '))')->doesItCut(
                InterfaceToCall::create(MethodInterceptorWithoutAspectExample::class, 'doSomething'),
                [new AttributeTwo()],
                InterfaceToCallRegistry::createEmpty()
            )
        );
    }

    public function test_negation_with_and_operator()
    {
        $this->assertTrue(
            Pointcut::createWith('not(' . AttributeOne::class . ') && ' . AttributeTwo::class)->doesItCut(
                InterfaceToCall::create(MethodInterceptorWithoutAspectExample::class, 'doSomething'),
                [new AttributeTwo()],
                InterfaceToCallRegistry::createEmpty()
            )
        );

        $this->assertFalse(
            Pointcut::createWith('not(' . AttributeOne::class . ') && ' . AttributeTwo::class)->doesItCut(
                InterfaceToCall::create(MethodInterceptorWithoutAspectExample::class, 'doSomething'),
                [new AttributeOne()],
                InterfaceToCallRegistry::createEmpty()
            )
        );
    }

    public function test_negation_with_or_operator()
    {
        $this->assertTrue(
            Pointcut::createWith('not(' . AttributeOne::class . ') || ' . AttributeTwo::class)->doesItCut(
                InterfaceToCall::create(MethodInterceptorWithoutAspectExample::class, 'doSomething'),
                [new AttributeTwo()],
                InterfaceToCallRegistry::createEmpty()
            )
        );

        $this->assertTrue(
            Pointcut::createWith('not(' . AttributeOne::class . ') || ' . AttributeTwo::class)->doesItCut(
                InterfaceToCall::create(MethodInterceptorWithoutAspectExample::class, 'doSomething'),
                [],
                InterfaceToCallRegistry::createEmpty()
            )
        );

        $this->assertFalse(
            Pointcut::createWith('not(' . AttributeOne::class . ') || ' . AttributeTwo::class)->doesItCut(
                InterfaceToCall::create(MethodInterceptorWithoutAspectExample::class, 'doSomething'),
                [new AttributeOne()],
                InterfaceToCallRegistry::createEmpty()
            )
        );
    }

    public function test_negation_with_nested_parentheses()
    {
        $this->assertFalse(
            Pointcut::createWith('not((' . AttributeOne::class . ' && ' . AttributeTwo::class . '))')->doesItCut(
                InterfaceToCall::create(MethodInterceptorWithoutAspectExample::class, 'doSomething'),
                [new AttributeOne(), new AttributeTwo()],
                InterfaceToCallRegistry::createEmpty()
            )
        );

        $this->assertTrue(
            Pointcut::createWith('not((' . AttributeOne::class . ' && ' . AttributeTwo::class . '))')->doesItCut(
                InterfaceToCall::create(MethodInterceptorWithoutAspectExample::class, 'doSomething'),
                [new AttributeOne()],
                InterfaceToCallRegistry::createEmpty()
            )
        );
    }

    public function test_negation_with_complex_expression()
    {
        $this->assertTrue(
            Pointcut::createWith('not(' . AttributeOne::class . ' || ' . AttributeTwo::class . ')')->doesItCut(
                InterfaceToCall::create(MethodInterceptorWithoutAspectExample::class, 'doSomething'),
                [],
                InterfaceToCallRegistry::createEmpty()
            )
        );

        $this->assertFalse(
            Pointcut::createWith('not(' . AttributeOne::class . ' || ' . AttributeTwo::class . ')')->doesItCut(
                InterfaceToCall::create(MethodInterceptorWithoutAspectExample::class, 'doSomething'),
                [new AttributeOne()],
                InterfaceToCallRegistry::createEmpty()
            )
        );

        $this->assertFalse(
            Pointcut::createWith('not(' . AttributeOne::class . ' || ' . AttributeTwo::class . ')')->doesItCut(
                InterfaceToCall::create(MethodInterceptorWithoutAspectExample::class, 'doSomething'),
                [new AttributeTwo()],
                InterfaceToCallRegistry::createEmpty()
            )
        );
    }

    public function test_negation_with_class_expression()
    {
        $this->assertFalse(
            Pointcut::createWith('not(' . MethodInterceptorWithoutAspectExample::class . ')')->doesItCut(
                InterfaceToCall::create(MethodInterceptorWithoutAspectExample::class, 'doSomething'),
                [],
                InterfaceToCallRegistry::createEmpty()
            )
        );

        $this->assertTrue(
            Pointcut::createWith('not(' . MethodInterceptorWithoutAspectExample::class . ')')->doesItCut(
                InterfaceToCall::create(CallMultipleUnorderedArgumentsInvocationInterceptorExample::class, 'callMultipleUnorderedArgumentsInvocation'),
                [],
                InterfaceToCallRegistry::createEmpty()
            )
        );
    }

    public function test_negation_with_method_expression()
    {
        $this->assertFalse(
            Pointcut::createWith('not(' . MethodInterceptorWithoutAspectExample::class . '::doSomething)')->doesItCut(
                InterfaceToCall::create(MethodInterceptorWithoutAspectExample::class, 'doSomething'),
                [],
                InterfaceToCallRegistry::createEmpty()
            )
        );

        $this->assertTrue(
            Pointcut::createWith('not(' . MethodInterceptorWithoutAspectExample::class . '::doSomething)')->doesItCut(
                InterfaceToCall::create(CallMultipleUnorderedArgumentsInvocationInterceptorExample::class, 'callMultipleUnorderedArgumentsInvocation'),
                [],
                InterfaceToCallRegistry::createEmpty()
            )
        );
    }

    public function test_negation_with_regex_expression()
    {
        $this->assertFalse(
            Pointcut::createWith('not(Test\Ecotone\Messaging\Fixture\Handler\Processor\*)')->doesItCut(
                InterfaceToCall::create(MethodInterceptorWithoutAspectExample::class, 'doSomething'),
                [],
                InterfaceToCallRegistry::createEmpty()
            )
        );

        // Test that negation works with regex - the regex should match the class, so negation should return false
        // This test verifies that the regex matching is working correctly within the negation
        $this->assertFalse(
            Pointcut::createWith('not(Test\Ecotone\Messaging\Fixture\Handler\Processor\*)')->doesItCut(
                InterfaceToCall::create(CallMultipleUnorderedArgumentsInvocationInterceptorExample::class, 'callMultipleUnorderedArgumentsInvocation'),
                [],
                InterfaceToCallRegistry::createEmpty()
            )
        );
    }

    public function test_negation_error_cases()
    {
        $this->expectException(Pointcut\IncorrectPointcutException::class);
        Pointcut::createWith('not()');

        $this->expectException(Pointcut\IncorrectPointcutException::class);
        Pointcut::createWith('not(' . AttributeOne::class);

        $this->expectException(Pointcut\IncorrectPointcutException::class);
        Pointcut::createWith('not(' . AttributeOne::class . '))');
    }


    private function itShouldCut(string $expression, InterfaceToCall $doesItCut): void
    {
        $this->assertTrue(Pointcut::createWith($expression)->doesItCut($doesItCut, [], InterfaceToCallRegistry::createEmpty()));
    }

    private function itShouldNotCut(string $expression, InterfaceToCall $doesItCut): void
    {
        $this->assertFalse(Pointcut::createWith($expression)->doesItCut($doesItCut, [], InterfaceToCallRegistry::createEmpty()));
    }
}
