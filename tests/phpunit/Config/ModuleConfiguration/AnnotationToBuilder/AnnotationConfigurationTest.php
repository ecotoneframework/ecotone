<?php

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Config\ModuleConfiguration\AnnotationToBuilder;

use Doctrine\Common\Annotations\AnnotationReader;
use Fixture\Configuration\DumbConfigurationObserver;
use Fixture\Configuration\DumbModuleConfigurationRetrievingService;
use SimplyCodedSoftware\IntegrationMessaging\Config\InMemoryConfigurationVariableRetrievingService;
use SimplyCodedSoftware\IntegrationMessaging\Config\MessagingSystemConfiguration;
use SimplyCodedSoftware\IntegrationMessaging\Config\ModuleConfiguration\AnnotationConfiguration;
use SimplyCodedSoftware\IntegrationMessaging\Config\ModuleConfiguration\DoctrineClassMetadataReader;
use SimplyCodedSoftware\IntegrationMessaging\Config\ModuleConfiguration\FileSystemClassLocator;
use Test\SimplyCodedSoftware\IntegrationMessaging\MessagingTest;

/**
 * Class AnnotationConfigurationTest
 * @package Test\SimplyCodedSoftware\IntegrationMessaging\Config\ModuleConfiguration\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
abstract class AnnotationConfigurationTest extends MessagingTest
{
    /**
     * @var AnnotationConfiguration
     */
    protected $annotationConfiguration;

    public function setUp()
    {
        $annotationConfiguration = $this->createAnnotationConfiguration();
        $annotationReader = $this->createAnnotationReader();

        $annotationConfiguration->setClassLocator(new FileSystemClassLocator(
            $annotationReader,
            [
                self::FIXTURE_DIR . "/Annotation"
            ],
            [
                "Fixture\Annotation\\" . $this->getPartOfTheNamespaceForTests()
            ]
        ));
        $annotationConfiguration->setClassMetadataReader(new DoctrineClassMetadataReader(
            $annotationReader
        ));

        $this->annotationConfiguration = $annotationConfiguration;
    }

    /**
     * @return MessagingSystemConfiguration
     */
    protected function createMessagingSystemConfiguration(): MessagingSystemConfiguration
    {
        return MessagingSystemConfiguration::prepare(
            DumbModuleConfigurationRetrievingService::createEmpty(),
            InMemoryConfigurationVariableRetrievingService::createEmpty(),
            DumbConfigurationObserver::create()
        );
    }

    /**
     * @return AnnotationConfiguration
     */
    protected abstract function createAnnotationConfiguration() : AnnotationConfiguration;

    /**
     * @return string
     */
    protected abstract function getPartOfTheNamespaceForTests() : string;

    /**
     * @return AnnotationReader
     */
    private function createAnnotationReader(): AnnotationReader
    {
        return new AnnotationReader();
    }
}