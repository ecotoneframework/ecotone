<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\Messaging\Unit\Config\Annotation\ModuleConfiguration;

use Test\SimplyCodedSoftware\Messaging\Builder\Annotation\ServiceActivatorAnnotationTestCaseBuilder;
use Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\Interceptor\ClassLevelInterceptorsAndMethodsExample;
use Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\Interceptor\ClassLevelInterceptorsExample;
use Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\Interceptor\EnrichInterceptorExample;
use Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\Interceptor\GatewayInterceptorExample;
use Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\Interceptor\ServiceActivatorMethodLevelInterceptorExample;
use Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\MessageEndpoint\ServiceActivator\AllConfigurationDefined\ServiceActivatorWithAllConfigurationDefined;
use SimplyCodedSoftware\Messaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\Messaging\Annotation\ServiceActivator;
use SimplyCodedSoftware\Messaging\Config\Annotation\InMemoryAnnotationRegistrationService;
use SimplyCodedSoftware\Messaging\Config\Annotation\ModuleConfiguration\MethodInterceptorModule;
use SimplyCodedSoftware\Messaging\Config\ConfigurableReferenceSearchService;
use SimplyCodedSoftware\Messaging\Config\ConfigurationException;
use SimplyCodedSoftware\Messaging\Config\NullObserver;
use SimplyCodedSoftware\Messaging\Config\OrderedMethodInterceptor;
use SimplyCodedSoftware\Messaging\Endpoint\ClassMethodInterceptor;
use SimplyCodedSoftware\Messaging\Handler\Enricher\Converter\EnrichHeaderWithExpressionBuilder;
use SimplyCodedSoftware\Messaging\Handler\Enricher\Converter\EnrichHeaderWithValueBuilder;
use SimplyCodedSoftware\Messaging\Handler\Enricher\Converter\EnrichPayloadWithExpressionBuilder;
use SimplyCodedSoftware\Messaging\Handler\Enricher\Converter\EnrichPayloadWithValueBuilder;
use SimplyCodedSoftware\Messaging\Handler\Enricher\EnricherBuilder;
use SimplyCodedSoftware\Messaging\Handler\Gateway\GatewayInterceptorBuilder;
use SimplyCodedSoftware\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Test\SimplyCodedSoftware\Messaging\Unit\Handler\ServiceActivator\ServiceActivatorBuilderTest;

/**
 * Class MethodInterceptorModuleTest
 * @package Test\SimplyCodedSoftware\Messaging\Unit\Config\Annotation\ModuleConfiguration
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MethodInterceptorModuleTest extends AnnotationConfigurationTest
{
    /**
     * @return mixed
     * @throws ConfigurationException
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_registering_method_level_interceptor()
    {
        $expectedConfiguration = $this->createMessagingSystemConfiguration()
            ->registerPreCallMethodInterceptor(
                OrderedMethodInterceptor::create(
                    ServiceActivatorBuilder::create("authorizationService", "check")
                        ->withEndpointId("some-id"),
                    2
                )
            )
            ->registerPostCallMethodInterceptor(
                OrderedMethodInterceptor::create(
                    ServiceActivatorBuilder::create("test", "check")
                        ->withEndpointId("some-id"),
                    1
                )
            );

        $annotationRegistrationService = InMemoryAnnotationRegistrationService::createFrom([
            ServiceActivatorMethodLevelInterceptorExample::class
        ]);
        $annotationConfiguration = MethodInterceptorModule::create($annotationRegistrationService);
        $configuration = $this->createMessagingSystemConfiguration();
        $annotationConfiguration->prepare($configuration, []);

        $this->assertEquals(
            $expectedConfiguration,
            $configuration
        );
    }

    /**
     * @throws ConfigurationException
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_throwing_exception_if_lack_of_endpoints_for_interceptors()
    {
        $annotationRegistrationService = InMemoryAnnotationRegistrationService::createFrom([
            ServiceActivatorMethodLevelInterceptorExample::class
        ])
            ->resetClassMethodAnnotation(ServiceActivatorMethodLevelInterceptorExample::class, "send", ServiceActivator::class);

        $this->expectException(ConfigurationException::class);

        $annotationConfiguration = MethodInterceptorModule::create($annotationRegistrationService);
        $configuration = $this->createMessagingSystemConfiguration();
        $annotationConfiguration->prepare($configuration, []);
    }

    /**
     * @throws ConfigurationException
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_registering_class_level_interceptors()
    {
        $expectedConfiguration = $this->createMessagingSystemConfiguration()
            ->registerPreCallMethodInterceptor(
                OrderedMethodInterceptor::create(
                    ServiceActivatorBuilder::create("authorizationService", "check")
                        ->withEndpointId("some-id"),
                    OrderedMethodInterceptor::DEFAULT_ORDER_WEIGHT
                )
            )
            ->registerPostCallMethodInterceptor(
                OrderedMethodInterceptor::create(
                    ServiceActivatorBuilder::create("test", "check")
                        ->withEndpointId("some-id"),
                    OrderedMethodInterceptor::DEFAULT_ORDER_WEIGHT
                )
            );

        $annotationRegistrationService = InMemoryAnnotationRegistrationService::createFrom([
            ClassLevelInterceptorsExample::class
        ]);
        $annotationConfiguration = MethodInterceptorModule::create($annotationRegistrationService);
        $configuration = $this->createMessagingSystemConfiguration();
        $annotationConfiguration->prepare($configuration, []);

        $this->assertEquals(
            $expectedConfiguration,
            $configuration
        );
    }

    /**
     * @throws ConfigurationException
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_registering_class_level_and_methods_together_interceptors()
    {
        $expectedConfiguration = $this->createMessagingSystemConfiguration()
            ->registerPreCallMethodInterceptor(
                OrderedMethodInterceptor::create(
                    ServiceActivatorBuilder::create("authorizationService", "check")
                        ->withEndpointId("some-id"),
                    OrderedMethodInterceptor::DEFAULT_ORDER_WEIGHT
                )
            )
            ->registerPreCallMethodInterceptor(
                OrderedMethodInterceptor::create(
                    ServiceActivatorBuilder::create("validationCheck", "check")
                        ->withEndpointId("some-id"),
                    OrderedMethodInterceptor::DEFAULT_ORDER_WEIGHT
                )
            )
            ->registerPostCallMethodInterceptor(
                OrderedMethodInterceptor::create(
                    ServiceActivatorBuilder::create("test", "check")
                        ->withEndpointId("some-id"),
                    OrderedMethodInterceptor::DEFAULT_ORDER_WEIGHT
                )
            );

        $annotationRegistrationService = InMemoryAnnotationRegistrationService::createFrom([
            ClassLevelInterceptorsAndMethodsExample::class
        ]);
        $annotationConfiguration = MethodInterceptorModule::create($annotationRegistrationService);
        $configuration = $this->createMessagingSystemConfiguration();
        $annotationConfiguration->prepare($configuration, []);

        $this->assertEquals(
            $expectedConfiguration,
            $configuration
        );
    }

    /**
     * @throws ConfigurationException
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_registering_enrich_interceptor()
    {
        $expectedConfiguration = $this->createMessagingSystemConfiguration()
            ->registerPreCallMethodInterceptor(
                OrderedMethodInterceptor::create(
                    EnricherBuilder::create([
                        EnrichPayloadWithExpressionBuilder::createWithMapping("orders[*][person]", "payload", "requestContext['personId'] == replyContext['personId']")
                            ->withNullResultExpression("reference('fakeData').get()"),
                        EnrichPayloadWithExpressionBuilder::createWith("session1", "'some1'"),
                        EnrichPayloadWithValueBuilder::createWith("session2", "some2"),
                        EnrichHeaderWithExpressionBuilder::createWith("token1", "'123'")
                            ->withNullResultExpression("'1234'"),
                        EnrichHeaderWithValueBuilder::create("token2", "1234")
                    ])
                        ->withEndpointId("some-id")
                        ->withRequestHeaders([
                            "token" => "1234"
                        ])
                        ->withRequestPayloadExpression("payload['name']")
                        ->withRequestMessageChannel("requestChannel"),
                    OrderedMethodInterceptor::DEFAULT_ORDER_WEIGHT
                )
            );

        $annotationRegistrationService = InMemoryAnnotationRegistrationService::createFrom([
            EnrichInterceptorExample::class
        ]);
        $annotationConfiguration = MethodInterceptorModule::create($annotationRegistrationService);
        $configuration = $this->createMessagingSystemConfiguration();
        $annotationConfiguration->prepare($configuration, []);

        $this->assertEquals(
            $expectedConfiguration,
            $configuration
        );
    }

    /**
     * @throws ConfigurationException
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_registering_gateway_interceptor()
    {
        $expectedConfiguration = $this->createMessagingSystemConfiguration()
            ->registerPreCallMethodInterceptor(
                OrderedMethodInterceptor::create(
                    GatewayInterceptorBuilder::create("requestChannel")
                        ->withEndpointId("some-id"),
                    OrderedMethodInterceptor::DEFAULT_ORDER_WEIGHT
                )
            );

        $annotationRegistrationService = InMemoryAnnotationRegistrationService::createFrom([
            GatewayInterceptorExample::class
        ]);
        $annotationConfiguration = MethodInterceptorModule::create($annotationRegistrationService);
        $configuration = $this->createMessagingSystemConfiguration();
        $annotationConfiguration->prepare($configuration, []);

        $this->assertEquals(
            $expectedConfiguration,
            $configuration
        );
    }
}