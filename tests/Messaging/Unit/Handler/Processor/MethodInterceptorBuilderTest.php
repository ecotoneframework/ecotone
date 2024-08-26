<?php

namespace Test\Ecotone\Messaging\Unit\Handler\Processor;

use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInterceptorBuilder;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\Messaging\Fixture\Annotation\Interceptor\ResolvedPointcut\AroundInterceptorExample;
use Test\Ecotone\Messaging\Fixture\Annotation\Interceptor\ResolvedPointcut\AttributeOne;

/**
 * Class MethodInterceptorTest
 * @package Test\Ecotone\Messaging\Unit\Handler\Processor
 * @author  Dariusz Gafka <support@simplycodedsoftware.com>
 *
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
class MethodInterceptorBuilderTest extends TestCase
{
    public function test_resolving_pointcut_automatically()
    {
        $this->assertEquals(
            MethodInterceptorBuilder::create(
                Reference::to(AroundInterceptorExample::class),
                InterfaceToCall::create(AroundInterceptorExample::class, 'withNonAnnotationClass'),
                1,
                '(' . AttributeOne::class . ')'
            ),
            MethodInterceptorBuilder::create(
                Reference::to(AroundInterceptorExample::class),
                InterfaceToCall::create(AroundInterceptorExample::class, 'withNonAnnotationClass'),
                1,
                ''
            )
        );
    }
}
