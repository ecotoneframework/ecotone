<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ModuleConfiguration;

use Builder\Annotation\ServiceActivatorAnnotationTestCaseBuilder;
use Fixture\Annotation\Interceptor\ClassLevelInterceptorsAndMethodsExample;
use Fixture\Annotation\Interceptor\ClassLevelInterceptorsExample;
use Fixture\Annotation\Interceptor\ServiceActivatorMethodLevelInterceptorExample;
use Fixture\Annotation\MessageEndpoint\ServiceActivator\AllConfigurationDefined\ServiceActivatorWithAllConfigurationDefined;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\ServiceActivator;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\InMemoryAnnotationRegistrationService;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ModuleConfiguration\MethodInterceptorModule;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationException;
use SimplyCodedSoftware\IntegrationMessaging\Config\NullObserver;
use SimplyCodedSoftware\IntegrationMessaging\Endpoint\ClassMethodInterceptor;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Test\SimplyCodedSoftware\IntegrationMessaging\Handler\ServiceActivator\ServiceActivatorBuilderTest;

/**
 * Class MethodInterceptorModuleTest
 * @package Test\SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ModuleConfiguration
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MethodInterceptorModuleTest extends AnnotationConfigurationTest
{
    /**
     * @return mixed
     * @throws ConfigurationException
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_registering_method_level_interceptor()
    {
        $expectedConfiguration = $this->createMessagingSystemConfiguration()
            ->registerPreCallMethodInterceptor(
                ServiceActivatorBuilder::create("authorizationService", "check")
                    ->withEndpointId("some-id")
            )
            ->registerPostCallMethodInterceptor(
                ServiceActivatorBuilder::create("test", "check")
                    ->withEndpointId("some-id")
            );

        $annotationRegistrationService = InMemoryAnnotationRegistrationService::createFrom([
            ServiceActivatorMethodLevelInterceptorExample::class
        ]);
        $annotationConfiguration = MethodInterceptorModule::create($annotationRegistrationService);
        $configuration = $this->createMessagingSystemConfiguration();
        $annotationConfiguration->prepare($configuration, [], NullObserver::create());

        $this->assertEquals(
            $expectedConfiguration,
            $configuration
        );
    }

    /**
     * @throws ConfigurationException
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
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
        $annotationConfiguration->prepare($configuration, [], NullObserver::create());
    }

    /**
     * @throws ConfigurationException
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_registering_class_level_interceptors()
    {
        $expectedConfiguration = $this->createMessagingSystemConfiguration()
            ->registerPreCallMethodInterceptor(
                ServiceActivatorBuilder::create("authorizationService", "check")
                    ->withEndpointId("some-id")
            )
            ->registerPostCallMethodInterceptor(
                ServiceActivatorBuilder::create("test", "check")
                    ->withEndpointId("some-id")
            );

        $annotationRegistrationService = InMemoryAnnotationRegistrationService::createFrom([
            ClassLevelInterceptorsExample::class
        ]);
        $annotationConfiguration = MethodInterceptorModule::create($annotationRegistrationService);
        $configuration = $this->createMessagingSystemConfiguration();
        $annotationConfiguration->prepare($configuration, [], NullObserver::create());

        $this->assertEquals(
            $expectedConfiguration,
            $configuration
        );
    }

    /**
     * @throws ConfigurationException
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_registering_class_level_and_methods_together_interceptors()
    {
        $expectedConfiguration = $this->createMessagingSystemConfiguration()
            ->registerPreCallMethodInterceptor(
                ServiceActivatorBuilder::create("authorizationService", "check")
                    ->withEndpointId("some-id")
            )
            ->registerPreCallMethodInterceptor(
                ServiceActivatorBuilder::create("validationCheck", "check")
                    ->withEndpointId("some-id")
            )
            ->registerPostCallMethodInterceptor(
                ServiceActivatorBuilder::create("test", "check")
                    ->withEndpointId("some-id")
            );

        $annotationRegistrationService = InMemoryAnnotationRegistrationService::createFrom([
            ClassLevelInterceptorsAndMethodsExample::class
        ]);
        $annotationConfiguration = MethodInterceptorModule::create($annotationRegistrationService);
        $configuration = $this->createMessagingSystemConfiguration();
        $annotationConfiguration->prepare($configuration, [], NullObserver::create());

        $this->assertEquals(
            $expectedConfiguration,
            $configuration
        );
    }
}