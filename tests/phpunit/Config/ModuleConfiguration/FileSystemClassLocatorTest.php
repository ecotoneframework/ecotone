<?php

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Config\ModuleConfiguration;

use Doctrine\Common\Annotations\AnnotationReader;
use Fixture\Annotation\FileSystem\DumbModuleConfiguration;
use SimplyCodedSoftware\IntegrationMessaging\Config\ModuleConfiguration\FileSystemClassLocator;
use Test\SimplyCodedSoftware\IntegrationMessaging\MessagingTest;

/**
 * Class FileSystemClassLocatorTest
 * @package Test\SimplyCodedSoftware\IntegrationMessaging\Config\ModuleConfiguration
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class FileSystemClassLocatorTest extends MessagingTest
{
    public function test_retrieving_module_configuration_by_annotation()
    {
        $fileSystemClassLocator = new FileSystemClassLocator(
            new AnnotationReader(),
            [
            __DIR__ . DIRECTORY_SEPARATOR . '../../../Fixture/Annotation/FileSystem',
            ],
            [
               'Fixture\Annotation\FileSystem'
            ]
        );

        $classes = $fileSystemClassLocator->getAllClasses();

        $this->assertCount(1, $classes, "File system class locator didn't find module configuration");
        $this->assertEquals(DumbModuleConfiguration::class, $classes[0]);
    }
}