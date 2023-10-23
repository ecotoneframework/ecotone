<?php

namespace Test\Ecotone\Messaging\Unit\Handler\Processor;

use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\AttributeBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\ValueBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInterceptor;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\Messaging\Fixture\Annotation\Interceptor\InterceptorWithMultipleOptionalAttributes;
use Test\Ecotone\Messaging\Fixture\Annotation\Interceptor\ResolvedPointcut\AroundInterceptorExample;
use Test\Ecotone\Messaging\Fixture\Annotation\Interceptor\ResolvedPointcut\AttributeOne;
use Test\Ecotone\Messaging\Fixture\Behat\Calculating\AfterMultiplyCalculation;
use Test\Ecotone\Messaging\Fixture\Behat\Calculating\BeforeMultiplyCalculation;
use Test\Ecotone\Messaging\Fixture\Behat\Calculating\Calculator;
use Test\Ecotone\Messaging\Fixture\Behat\Calculating\CalculatorInterceptor;
use Test\Ecotone\Messaging\Fixture\Behat\Calculating\PowerCalculation;

/**
 * Class MethodInterceptorTest
 * @package Test\Ecotone\Messaging\Unit\Handler\Processor
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 *
 * @internal
 */
class MethodInterceptorTest extends TestCase
{
    public function test_adding_parameters_when_type_hinting_for_annotation_class()
    {
        $interceptorInterface = InterfaceToCall::create(CalculatorInterceptor::class, 'multiplyBefore');
        $methodInterceptor = MethodInterceptor::create(
            CalculatorInterceptor::class,
            $interceptorInterface,
            ServiceActivatorBuilder::create(CalculatorInterceptor::class, InterfaceToCall::create(CalculatorInterceptor::class, 'multiplyBefore')),
            1,
            ''
        );

        $this->assertEquals(
            [
                new AttributeBuilder('beforeMultiplyCalculation', new BeforeMultiplyCalculation(2), Calculator::class, 'calculate'),
            ],
            $methodInterceptor->addInterceptedInterfaceToCall(InterfaceToCall::create(Calculator::class, 'calculate'), [])
                ->getInterceptingObject()
                ->getParameterConverters()
        );
    }

    public function test_resolving_nullable_attributes(): void
    {
        $interceptorInterface = InterfaceToCall::create(InterceptorWithMultipleOptionalAttributes::class, 'doSomething');
        $methodInterceptor = MethodInterceptor::create(
            InterceptorWithMultipleOptionalAttributes::class,
            $interceptorInterface,
            ServiceActivatorBuilder::create(InterceptorWithMultipleOptionalAttributes::class, $interceptorInterface),
            1,
            BeforeMultiplyCalculation::class . '|' . AfterMultiplyCalculation::class . '|' . PowerCalculation::class
        );

        $this->assertEquals(
            [
                new AttributeBuilder('beforeMultiplyCalculation', new BeforeMultiplyCalculation(2), Calculator::class, 'calculate'),
                new AttributeBuilder('afterMultiplyCalculation', new AfterMultiplyCalculation(2), Calculator::class, 'calculate'),
                new ValueBuilder('powerCalculation', null),
            ],
            $methodInterceptor->addInterceptedInterfaceToCall(InterfaceToCall::create(Calculator::class, 'calculate'), [])
                ->getInterceptingObject()
                ->getParameterConverters()
        );
    }

    public function test_resolving_pointcut_automatically()
    {
        $this->assertEquals(
            MethodInterceptor::create(
                AroundInterceptorExample::class,
                InterfaceToCall::create(AroundInterceptorExample::class, 'withNonAnnotationClass'),
                ServiceActivatorBuilder::create(AroundInterceptorExample::class, InterfaceToCall::create(AroundInterceptorExample::class, 'withNonAnnotationClass')),
                1,
                '(' . AttributeOne::class . ')'
            ),
            MethodInterceptor::create(
                AroundInterceptorExample::class,
                InterfaceToCall::create(AroundInterceptorExample::class, 'withNonAnnotationClass'),
                ServiceActivatorBuilder::create(AroundInterceptorExample::class, InterfaceToCall::create(AroundInterceptorExample::class, 'withNonAnnotationClass')),
                1,
                ''
            )
        );
    }
}
