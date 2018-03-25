<?php

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ModuleConfiguration;

use Fixture\Annotation\ApplicationContext\ApplicationContextExample;
use Fixture\Annotation\ApplicationContext\GatewayExample;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\ApplicationContextAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessagingComponentAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Channel\SimpleMessageChannelBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationModule;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\InMemoryAnnotationRegistrationService;
use SimplyCodedSoftware\IntegrationMessaging\Config\Configuration;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationException;
use SimplyCodedSoftware\IntegrationMessaging\Config\InMemoryConfigurationVariableRetrievingService;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ModuleConfiguration\ApplicationContextModule;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\GatewayProxyBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Transformer\TransformerBuilder;

/**
 * Class AnnotationApplicationContextConfigurationTest
 * @package Test\SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ApplicationContextModuleTest extends AnnotationConfigurationTest
{
    /**
     * @throws ConfigurationException
     */
    public function test_configuring_message_channel_from_application_context()
    {
        $expectedConfiguration = $this->createMessagingSystemConfiguration()
            ->registerMessageChannel(SimpleMessageChannelBuilder::createDirectMessageChannel(ApplicationContextExample::HTTP_INPUT_CHANNEL));

        $this->compareWithConfiguredForMethod("httpEntryChannel", $expectedConfiguration);
    }

    /**
     * @throws ConfigurationException
     */
    public function test_configuring_message_handler_from_application_context()
    {
        $expectedConfiguration = $this->createMessagingSystemConfiguration()
            ->registerMessageHandler(TransformerBuilder::createHeaderEnricher(ApplicationContextExample::HTTP_INPUT_CHANNEL, [
                "token" => "abcedfg"
            ])->withOutputMessageChannel(ApplicationContextExample::HTTP_OUTPUT_CHANNEL));

        $this->compareWithConfiguredForMethod("enricherHttpEntry", $expectedConfiguration);
    }

    /**
     * @throws ConfigurationException
     */
    public function test_configuring_gateway_from_application_context()
    {
        $expectedConfiguration = $this->createMessagingSystemConfiguration()
            ->registerGatewayBuilder(GatewayProxyBuilder::create("some-ref", GatewayExample::class, "doSomething", ApplicationContextExample::HTTP_INPUT_CHANNEL));

        $this->compareWithConfiguredForMethod("gateway", $expectedConfiguration);
    }

    /**
     * @throws ConfigurationException
     */
    public function test_throwing_exception_if_trying_to_register_not_known_messaging_component()
    {
        $this->checkForWrongConfiguration("wrongMessagingComponent");
    }

    /**
     * @param string $methodName
     * @param Configuration $expectedConfiguration
     * @throws ConfigurationException
     */
    private function compareWithConfiguredForMethod(string $methodName, Configuration $expectedConfiguration): void
    {
        $annotationConfiguration = $this->createAnnotationConfiguration($methodName);

        $configuration = $this->createMessagingSystemConfiguration();

        $annotationConfiguration->registerWithin($configuration, [], InMemoryConfigurationVariableRetrievingService::createEmpty());

        $this->assertEquals(
            $expectedConfiguration,
            $configuration
        );
    }

    /**
     * @param string $methodName
     * @throws ConfigurationException
     */
    private function checkForWrongConfiguration(string $methodName) : void
    {
        $this->expectException(ConfigurationException::class);

        $annotationConfiguration = $this->createAnnotationConfiguration($methodName);
        $configuration = $this->createMessagingSystemConfiguration();
        $annotationConfiguration->registerWithin($configuration, [], InMemoryConfigurationVariableRetrievingService::createEmpty());
    }

    /**
     * @param $methodName
     * @return \SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationModule
     */
    private function createAnnotationConfiguration($methodName): \SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationModule
    {
        $annotationRegistrationService = InMemoryAnnotationRegistrationService::createEmpty()
            ->addAnnotationToClass(
                ApplicationContextExample::class,
                new ApplicationContextAnnotation()
            )
            ->addAnnotationToClassMethod(
                ApplicationContextExample::class,
                $methodName,
                new MessagingComponentAnnotation()
            );

        $annotationConfiguration = ApplicationContextModule::create($annotationRegistrationService);

        return $annotationConfiguration;
    }
}