<?php


namespace Test\Ecotone\Messaging\Unit\Handler\Processor;

use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\ReferenceBuilder;
use PHPUnit\Framework\TestCase;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\AllHeadersBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\InterceptorConverterBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\PayloadBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInterceptor;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Test\Ecotone\Messaging\Fixture\Annotation\Interceptor\ResolvedPointcut\AroundInterceptorExample;
use Test\Ecotone\Messaging\Fixture\Annotation\Interceptor\ResolvedPointcut\AttributeOne;
use Test\Ecotone\Messaging\Fixture\Annotation\Interceptor\ServiceActivatorInterceptorWithServicesExample;
use Test\Ecotone\Messaging\Fixture\Behat\Calculating\Calculator;
use Test\Ecotone\Messaging\Fixture\Behat\Calculating\CalculatorInterceptor;
use Test\Ecotone\Messaging\Fixture\Service\CalculatingService;
use Test\Ecotone\Messaging\Fixture\Service\ServiceInterface\ServiceInterfaceSendOnly;

/**
 * Class MethodInterceptorTest
 * @package Test\Ecotone\Messaging\Unit\Handler\Processor
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MethodInterceptorTest extends TestCase
{
    public function test_adding_parameters_when_type_hinting_for_annotation_class()
    {
        $methodInterceptor = MethodInterceptor::create(CalculatorInterceptor::class, InterfaceToCall::create(CalculatorInterceptor::class, "multiplyBefore"), ServiceActivatorBuilder::create(CalculatorInterceptor::class, "multiplyBefore"), 1, "");

        $this->assertEquals(
            [
                InterceptorConverterBuilder::create("beforeMultiplyCalculation", InterfaceToCall::create(Calculator::class, "calculate"), []),
                PayloadBuilder::create("amount"),
                AllHeadersBuilder::createWith("metadata")
            ],
            $methodInterceptor->addInterceptedInterfaceToCall(InterfaceToCall::create(Calculator::class, "calculate"), [])
                ->getInterceptingObject()
                ->getParameterConverters()
        );
    }

    public function test_adding_reference_parameter_converters()
    {
        $methodInterceptor = MethodInterceptor::create(ServiceActivatorInterceptorWithServicesExample::class, InterfaceToCall::create(ServiceActivatorInterceptorWithServicesExample::class, "doSomethingBefore"), ServiceActivatorBuilder::create(ServiceActivatorInterceptorWithServicesExample::class, "doSomethingBefore"), 1, "");

        $this->assertEquals(
            [
                PayloadBuilder::create("name"),
                AllHeadersBuilder::createWith("metadata"),
                ReferenceBuilder::create("stdClass", \stdClass::class)
            ],
            $methodInterceptor->addInterceptedInterfaceToCall(InterfaceToCall::create(CalculatorInterceptor::class, "multiplyBefore"), [])
                ->getInterceptingObject()
                ->getParameterConverters()
        );
    }

    public function test_resolving_pointcut_automatically()
    {
        $this->assertEquals(
            MethodInterceptor::create(AroundInterceptorExample::class, InterfaceToCall::create(AroundInterceptorExample::class, "withNonAnnotationClass"), ServiceActivatorBuilder::create(AroundInterceptorExample::class, "withNonAnnotationClass"), 1,
                "(" . AttributeOne::class . ")"),
            MethodInterceptor::create(AroundInterceptorExample::class, InterfaceToCall::create(AroundInterceptorExample::class, "withNonAnnotationClass"), ServiceActivatorBuilder::create(AroundInterceptorExample::class, "withNonAnnotationClass"), 1,
                "")
        );
    }
}