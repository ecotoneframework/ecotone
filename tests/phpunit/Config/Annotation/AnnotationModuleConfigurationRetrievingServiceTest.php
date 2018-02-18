<?php

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Config\Annotation;

use Doctrine\Common\Annotations\AnnotationReader;
use Fixture\Annotation\ModuleConfiguration\WithExtensions\SimpleExtensionModuleConfiguration;
use Fixture\Annotation\ModuleConfiguration\WithExtensions\WithExtensionsModuleConfiguration;
use Fixture\Annotation\ModuleConfiguration\WithVariables\WithVariablesModuleConfiguration;
use Fixture\Configuration\DumbConfigurationObserver;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationModuleConfigurationRetrievingService;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\DoctrineClassMetadataReader;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\FileSystemClassLocator;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ModuleConfiguration\AnnotationApplicationContextConfiguration;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationException;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationObserver;
use SimplyCodedSoftware\IntegrationMessaging\Config\InMemoryConfigurationVariableRetrievingService;
use SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException;
use Test\SimplyCodedSoftware\IntegrationMessaging\MessagingTest;

/**
 * Class AnnotationModuleConfigurationRetrievingServiceTest
 * @package Test\SimplyCodedSoftware\IntegrationMessaging\Config\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AnnotationModuleConfigurationRetrievingServiceTest extends MessagingTest
{
    public function test_creating_annotation_module_configuration()
    {
        $annotationModuleConfigurationRetrievingService = $this->createAnnotationConfigurationRetrievingService("ModuleConfiguration\Simple", []);

        $this->assertCount(1, $annotationModuleConfigurationRetrievingService->findAllModuleConfigurations());
    }

    public function test_throwing_exception_if_not_extending_annotation_configuration()
    {
        $this->expectException(ConfigurationException::class);

        $this->createAnnotationConfigurationRetrievingService("ModuleConfiguration\Wrong", [])->findAllModuleConfigurations();
    }

    public function test_creating_with_variables()
    {
        $variables = [
            "token" => "234",
            "autologout" => "false"
        ];
        $modules = $this->createAnnotationConfigurationRetrievingService("ModuleConfiguration\WithVariables", $variables)->findAllModuleConfigurations();

        $this->assertEquals(
            $variables,
            $modules[0]->getVariables()
        );
    }

    public function test_creating_with_default_variables()
    {
        $variables = [
            "token" => "234"
        ];
        $modules = $this->createAnnotationConfigurationRetrievingService("ModuleConfiguration\WithVariables", $variables)->findAllModuleConfigurations();

        $this->assertEquals(
            [
                "token" => "234",
                "autologout" => "true"
            ],
            $modules[0]->getVariables()
        );
    }

    public function test_throwing_exception_if_variable_without_default_value_is_not_set()
    {
        $variables = [];

        $this->expectException(ConfigurationException::class);

        $this->createAnnotationConfigurationRetrievingService("ModuleConfiguration\WithVariables", $variables)->findAllModuleConfigurations();
    }

    public function test_throwing_exception_if_using_variable_not_defined_in_module_configuration()
    {
        $variables = [
            "token" => "234",
            "autologin" => "true"
        ];

        $this->expectException(InvalidArgumentException::class);

        $this->createAnnotationConfigurationRetrievingService("ModuleConfiguration\WrongVariables", $variables)->findAllModuleConfigurations();
    }

    public function test_creating_configuration_with_extension()
    {
        $variables = [
            "system" => "on",
            "debug" => "false"
        ];
        /** @var WithExtensionsModuleConfiguration[] $modules */
        $modules = $this->createAnnotationConfigurationRetrievingService("ModuleConfiguration\WithExtensions", $variables)->findAllModuleConfigurations();

        $this->assertEquals(
            [
                "system" => "on"
            ],
            $modules[0]->getVariables()
        );
        $this->assertEquals(
            [
                SimpleExtensionModuleConfiguration::create(InMemoryConfigurationVariableRetrievingService::create($variables))
            ],
            $modules[0]->getExtensions()
        );
    }

    public function test_throwing_exception_if_wrong_extension_passed()
    {
        $variables = [];

        $this->expectException(ConfigurationException::class);

        $this->createAnnotationConfigurationRetrievingService("ModuleConfiguration\WrongExtension", $variables)->findAllModuleConfigurations();
    }

    public function test_informing_observer_about_required_service_references()
    {
        $configurationObserver = DumbConfigurationObserver::create();
        $this->createAnnotationConfigurationRetrievingServiceWithObserver("ModuleConfiguration\WithReferences", [], $configurationObserver)->findAllModuleConfigurations();

        $this->assertEquals(
            ["some-service"],
            $configurationObserver->getRequiredReferences()
        );
    }

    private function createAnnotationConfigurationRetrievingService(string $namespacePart, array $configurationVariables) : AnnotationModuleConfigurationRetrievingService
    {
        return $this->createAnnotationConfigurationRetrievingServiceWithObserver($namespacePart, $configurationVariables, DumbConfigurationObserver::create());
    }

    private function createAnnotationConfigurationRetrievingServiceWithObserver(string $namespacePart, array $configurationVariables, ConfigurationObserver $configurationObserver) : AnnotationModuleConfigurationRetrievingService
    {
        $annotationReader = new AnnotationReader();

        return new AnnotationModuleConfigurationRetrievingService(
            InMemoryConfigurationVariableRetrievingService::create($configurationVariables),
            $configurationObserver,
            new FileSystemClassLocator(
                $annotationReader,
                [
                    self::FIXTURE_DIR . "/Annotation"
                ],
                [
                    "Fixture\Annotation\\" . $namespacePart
                ]
            ),
            new DoctrineClassMetadataReader(
                $annotationReader
            )
        );
    }
}