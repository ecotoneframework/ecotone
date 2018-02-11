<?php

namespace Test\SimplyCodedSoftware\Messaging\Config\ModuleConfiguration\Annotation;

use Doctrine\Common\Annotations\AnnotationReader;
use Fixture\Configuration\DumbConfigurationObserver;
use Fixture\Configuration\DumbModuleConfigurationRetrievingService;
use SimplyCodedSoftware\Messaging\Config\MessagingSystemConfiguration;
use SimplyCodedSoftware\Messaging\Config\ModuleConfiguration\AnnotationConfiguration;
use SimplyCodedSoftware\Messaging\Config\ModuleConfiguration\DoctrineClassMetadataReader;
use SimplyCodedSoftware\Messaging\Config\ModuleConfiguration\FileSystemClassLocator;
use Test\SimplyCodedSoftware\Messaging\MessagingTest;

/**
 * Class AnnotationConfigurationTest
 * @package Test\SimplyCodedSoftware\Messaging\Config\ModuleConfiguration\Annotation
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