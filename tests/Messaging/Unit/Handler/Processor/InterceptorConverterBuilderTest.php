<?php
declare(strict_types=1);


namespace Test\Ecotone\Messaging\Unit\Handler\Processor;

use Doctrine\Common\Annotations\AnnotationException;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use Ecotone\Messaging\Annotation\Interceptor\MethodInterceptor;
use Ecotone\Messaging\Handler\InMemoryReferenceSearchService;
use Ecotone\Messaging\Handler\InterfaceParameter;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\InterceptorConverterBuilder;
use Ecotone\Messaging\Handler\TypeDefinitionException;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Messaging\Transaction\Transactional;
use Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor\CallWithUnorderedClassInvocationInterceptorExample;
use Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor\TransactionalInterceptorExample;
use Test\Ecotone\Messaging\Fixture\Service\CallableService;
use Test\Ecotone\Messaging\Fixture\Service\ServiceWithoutReturnValue;

/**
 * Class InterceptorConverterBuilderTest
 * @package Test\Ecotone\Messaging\Unit\Handler\Processor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class InterceptorConverterBuilderTest extends TestCase
{
    /**
     * @throws AnnotationException
     * @throws ReflectionException
     * @throws TypeDefinitionException
     * @throws MessagingException
     * @throws InvalidArgumentException
     */
    public function test_retrieving_intercepted_method_annotation()
    {
        $interfaceToCall = InterfaceToCall::create(TransactionalInterceptorExample::class, "doAction");
        $converter = InterceptorConverterBuilder::create("some", $interfaceToCall, []);
        $converter = $converter->build(InMemoryReferenceSearchService::createEmpty());

        $parameter = InterfaceParameter::createNotNullable("some", TypeDescriptor::create(Transactional::class));
        $methodAnnotation = Transactional::createWith(["reference2"]);

        $this->assertTrue($converter->isHandling($parameter));
        $this->assertEquals(
            $methodAnnotation,
            $converter->getArgumentFrom(
                InterfaceToCall::create(CallableService::class, "wasCalled"),
                $parameter,
                MessageBuilder::withPayload("a")->setHeader("token", 123)->build(),
                []
            )
        );
    }

    /**
     * @throws AnnotationException
     * @throws ReflectionException
     * @throws TypeDefinitionException
     * @throws MessagingException
     * @throws InvalidArgumentException
     */
    public function test_retrieving_intercepted_class_annotation()
    {
        $interfaceToCall = InterfaceToCall::create(CallWithUnorderedClassInvocationInterceptorExample::class, "callWithUnorderedClassInvocation");
        $converter = InterceptorConverterBuilder::create("some", $interfaceToCall, []);
        $converter = $converter->build(InMemoryReferenceSearchService::createEmpty());

        $parameter = InterfaceParameter::createNotNullable("some", TypeDescriptor::create(MethodInterceptor::class));
        $classAnnotation = new MethodInterceptor();

        $this->assertTrue($converter->isHandling($parameter));
        $this->assertEquals(
            $classAnnotation,
            $converter->getArgumentFrom(
                InterfaceToCall::create(CallableService::class, "wasCalled"),
                $parameter,
                MessageBuilder::withPayload("a")->setHeader("token", 123)->build(),
                []
            )
        );
    }

    /**
     * @throws AnnotationException
     * @throws ReflectionException
     * @throws TypeDefinitionException
     * @throws MessagingException
     * @throws InvalidArgumentException
     */
    public function test_retrieving_intercepted_endpoint_annotation()
    {
        $interfaceToCall = InterfaceToCall::create(TransactionalInterceptorExample::class, "doAction");

        $endpointAnnotation = Transactional::createWith(["reference10000"]);
        $converter = InterceptorConverterBuilder::create("some", $interfaceToCall, [
            $endpointAnnotation
        ]);
        $converter = $converter->build(InMemoryReferenceSearchService::createEmpty());

        $parameter = InterfaceParameter::createNotNullable("some", TypeDescriptor::create(Transactional::class));

        $this->assertTrue($converter->isHandling($parameter));
        $this->assertEquals(
            $endpointAnnotation,
            $converter->getArgumentFrom(
                InterfaceToCall::create(CallableService::class, "wasCalled"),
                $parameter,
                MessageBuilder::withPayload("a")->setHeader("token", 123)->build(),
                []
            )
        );
    }

    /**
     * @throws AnnotationException
     * @throws ReflectionException
     * @throws TypeDefinitionException
     * @throws MessagingException
     * @throws InvalidArgumentException
     */
    public function test_throwing_exception_if_no_annotation_found()
    {
        $interfaceToCall = InterfaceToCall::create(ServiceWithoutReturnValue::class, "callWithInterfaceToCall");
        $converter = InterceptorConverterBuilder::create("some", $interfaceToCall, []);
        $converter = $converter->build(InMemoryReferenceSearchService::createEmpty());

        $interfaceParameter = InterfaceParameter::createNullable("x", TypeDescriptor::createWithDocBlock(Transactional::class, ""));

        $this->expectException(InvalidArgumentException::class);

        $this->assertEquals(
            $interfaceToCall,
            $converter->getArgumentFrom(
                InterfaceToCall::create(CallableService::class, "wasCalled"),
                $interfaceParameter,
                MessageBuilder::withPayload("a")->setHeader("token", 123)->build(),
                []
            )
        );
    }
}