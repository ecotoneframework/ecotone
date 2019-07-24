<?php


namespace Test\SimplyCodedSoftware\Messaging\Unit\Handler\Processor;

use PHPUnit\Framework\TestCase;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\Converter\AllHeadersBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\Converter\InterceptorConverterBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\Converter\PayloadBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\MethodInterceptor;
use SimplyCodedSoftware\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Test\SimplyCodedSoftware\Messaging\Fixture\Service\CalculatingService;
use Test\SimplyCodedSoftware\Messaging\Fixture\Service\ServiceInterface\ServiceInterfaceSendOnly;

/**
 * Class MethodInterceptorTest
 * @package Test\SimplyCodedSoftware\Messaging\Unit\Handler\Processor
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MethodInterceptorTest extends TestCase
{
    public function test_adding_parameter_converters_for_payload_headers_interface()
    {
        $methodInterceptor = MethodInterceptor::create(ServiceInterfaceSendOnly::class, InterfaceToCall::create(ServiceInterfaceSendOnly::class, "sendMailWithMetadata"), ServiceActivatorBuilder::create(ServiceInterfaceSendOnly::class, "sendMailWithMetadata"), 1, "");

        $this->assertEquals(
            [
                InterceptorConverterBuilder::create(InterfaceToCall::create(CalculatingService::class, "sum"), []), PayloadBuilder::create("content"), AllHeadersBuilder::createWith("metadata")
            ],
            $methodInterceptor->addInterceptedInterfaceToCall(InterfaceToCall::create(CalculatingService::class, "sum"), [])->getInterceptingObject()->getParameterConverters()
        );
    }
}