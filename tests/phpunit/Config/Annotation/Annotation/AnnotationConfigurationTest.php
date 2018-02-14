<?php

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\Annotation;

use Doctrine\Common\Annotations\AnnotationReader;
use Fixture\Configuration\DumbConfigurationObserver;
use Fixture\Configuration\DumbModuleConfigurationRetrievingService;
use SimplyCodedSoftware\IntegrationMessaging\Config\InMemoryConfigurationVariableRetrievingService;
use SimplyCodedSoftware\IntegrationMessaging\Config\MessagingSystemConfiguration;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationConfiguration;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\DoctrineClassMetadataReader;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\FileSystemClassLocator;
use Test\SimplyCodedSoftware\IntegrationMessaging\MessagingTest;

/**
 * Class AnnotationConfigurationTest
 * @package Test\SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\Annotation
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
        $annotationReader = new AnnotationReader();

        $this->annotationConfiguration = $this->createAnnotationConfiguration()::createAnnotationConfiguration(
            InMemoryConfigurationVariableRetrievingService::createEmpty(),
            new FileSystemClassLocator(
                $annotationReader,
                [
                    self::FIXTURE_DIR . "/Annotation"
                ],
                [
                    "Fixture\Annotation\\" . $this->getPartOfTheNamespaceForTests()
                ]
            ),
            new DoctrineClassMetadataReader(
                $annotationReader
            )
        );
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
     * @return string
     */
    protected abstract function createAnnotationConfiguration() : string;

    /**
     * @return string
     */
    protected abstract function getPartOfTheNamespaceForTests() : string;
}