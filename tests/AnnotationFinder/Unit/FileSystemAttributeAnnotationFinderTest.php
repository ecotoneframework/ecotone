<?php

namespace Test\Ecotone\AnnotationFinder\Unit;

use Ecotone\AnnotationFinder\AnnotatedDefinition;
use Ecotone\AnnotationFinder\AnnotatedMethod;
use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\AnnotationFinder\AnnotationResolver;
use Ecotone\AnnotationFinder\Attribute\Environment;
use Ecotone\AnnotationFinder\ConfigurationException;
use Ecotone\AnnotationFinder\FileSystem\AutoloadFileNamespaceParser;
use Ecotone\AnnotationFinder\FileSystem\FileSystemAnnotationFinder;
use Ecotone\AnnotationFinder\FileSystem\InMemoryAutoloadNamespaceParser;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\Annotation\EndpointAnnotationExample;
use Test\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\Annotation\Extension;
use Test\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\Annotation\MessageEndpoint;
use Test\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\Annotation\SomeGatewayExample;
use Test\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\Annotation\SomeHandlerAnnotation;
use Test\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\Annotation\System;
use Test\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\Environment\SystemContextWithClassEnvironment;
use Test\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\Environment\SystemContextWithMethodEnvironmentExample;
use Test\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\Environment\SystemContextWithMethodMultipleEnvironmentsExample;
use Test\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\MessageEndpoint\Gateway\FileSystem\GatewayWithReplyChannelExample;
use Test\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\MessageEndpoint\Splitter\SplitterExample;
use Test\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\MessageEndpoint\SplitterOnMethod\SplitterOnMethodExample;
use Test\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\NotExisting\NotExistingClassAttribute;
use Test\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\NotExisting\NotExistingMethodAttribute;
use Test\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\NotExisting\NotExistingPropertyAttribute;

class FileSystemAttributeAnnotationFinderTest extends TestCase
{
    const ROOT_DIR = __DIR__ . '/../../../';

    public function getAnnotationNamespacePrefix(): string
    {
        return "Test\\Ecotone\\AnnotationFinder\\Fixture\\Usage\\Attribute";
    }

    public function getAnnotationResolver(): AnnotationResolver
    {
        return new AnnotationResolver\AttributeResolver();
    }

    public function test_retrieving_annotation_registration_for_application_context()
    {
        $gatewayAnnotation = new SomeGatewayExample();
        $messageEndpoint   = new MessageEndpoint();
        $this->assertEquals(
            [
                AnnotatedDefinition::create(
                    $messageEndpoint,
                    $gatewayAnnotation,
                    GatewayWithReplyChannelExample::class,
                    "buy",
                    [$messageEndpoint],
                    [$gatewayAnnotation]
                )
            ],
            $this->createAnnotationRegistrationService($this->getAnnotationNamespacePrefix() . "\\MessageEndpoint\\Gateway\\FileSystem", "prod")
                ->findCombined(MessageEndpoint::class, SomeGatewayExample::class)
        );
    }

    public function test_retrieving_all_classes_with_annotation()
    {
        $classes = $this->createAnnotationRegistrationService($this->getAnnotationNamespacePrefix(), "prod")->findAnnotatedClasses(System::class);

        $this->assertNotEmpty($classes, "File system class locator didn't find application context");
    }

    public function test_retrieving_method_and_class_annotations()
    {
        $gatewayAnnotation = new SomeGatewayExample();

        $this->assertEquals(
            [
                $gatewayAnnotation
            ],
            $this->createAnnotationRegistrationService($this->getAnnotationNamespacePrefix() . "\\MessageEndpoint\Gateway\FileSystem", "prod")
                ->getAnnotationsForMethod(GatewayWithReplyChannelExample::class, "buy")
        );
    }

    public function test_retrieving_class_annotations()
    {
        $this->assertEquals(
            [
                new MessageEndpoint()
            ],
            $this->createAnnotationRegistrationService($this->getAnnotationNamespacePrefix() . "\\MessageEndpoint\Gateway\FileSystem", "prod")
                ->getAnnotationsForClass(GatewayWithReplyChannelExample::class)
        );
    }


    public function test_retrieving_for_specific_environment()
    {
        $fileSystemAnnotationRegistrationService = $this->createAnnotationRegistrationService($this->getAnnotationNamespacePrefix() . "\\Environment", "dev");
        $devEnvironment                          = new Environment(["dev"]);
        $prodDevEnvironment                      = new Environment(["prod", "dev"]);
        $prodEnvironment                         = new Environment(["prod"]);
        $allEnvironment                          = new Environment(["dev", "prod", "test"]);
        $methodAnnotation                        = new Extension();
        $System                      = new System();

        $this->assertEquals(
            [
                AnnotatedDefinition::create(
                    $System,
                    $methodAnnotation,
                    SystemContextWithMethodEnvironmentExample::class,
                    "configSingleEnvironment",
                    [$System, $prodDevEnvironment],
                    [$methodAnnotation, $devEnvironment]
                ),
                AnnotatedDefinition::create(
                    $System,
                    $methodAnnotation,
                    SystemContextWithMethodMultipleEnvironmentsExample::class,
                    "configMultipleEnvironments",
                    [$System],
                    [$methodAnnotation, $allEnvironment]
                )
            ],
            $fileSystemAnnotationRegistrationService->findCombined(System::class, Extension::class)
        );


        $fileSystemAnnotationRegistrationService = $this->createAnnotationRegistrationService($this->getAnnotationNamespacePrefix() . "\\Environment", "test");
        $this->assertEquals(
            [
                AnnotatedDefinition::create(
                    $System,
                    $methodAnnotation,
                    SystemContextWithMethodMultipleEnvironmentsExample::class,
                    "configMultipleEnvironments",
                    [$System],
                    [$methodAnnotation, $allEnvironment]
                )
            ],
            $fileSystemAnnotationRegistrationService->findCombined(System::class, Extension::class)
        );

        $fileSystemAnnotationRegistrationService = $this->createAnnotationRegistrationService($this->getAnnotationNamespacePrefix() . "\\Environment", "prod");
        $this->assertEquals(
            [
                AnnotatedDefinition::create(
                    $System,
                    $methodAnnotation,
                    SystemContextWithClassEnvironment::class,
                    "someAction",
                    [$System, $prodEnvironment],
                    [$methodAnnotation]
                ),
                AnnotatedDefinition::create(
                    $System,
                    $methodAnnotation,
                    SystemContextWithMethodMultipleEnvironmentsExample::class,
                    "configMultipleEnvironments",
                    [$System],
                    [$methodAnnotation, $allEnvironment]
                )
            ],
            $fileSystemAnnotationRegistrationService->findCombined(System::class, Extension::class)
        );
    }

    public function test_retrieving_subclass_annotation()
    {
        $annotation = new SomeHandlerAnnotation();

        $fileSystemAnnotationRegistrationService = $this->createAnnotationRegistrationService($this->getAnnotationNamespacePrefix() . "\\MessageEndpoint\\Splitter", "prod");

        $annotationForClass = new MessageEndpoint();
        $this->assertEquals(
            [
                AnnotatedDefinition::create(
                    $annotationForClass,
                    $annotation,
                    SplitterExample::class,
                    "split",
                    [$annotationForClass],
                    [$annotation]
                )
            ],
            $fileSystemAnnotationRegistrationService->findCombined(MessageEndpoint::class, EndpointAnnotationExample::class)
        );
    }

    public function test_retrieving_by_only_method_annotation()
    {
        $annotation = new SomeHandlerAnnotation();

        $fileSystemAnnotationRegistrationService = $this->createAnnotationRegistrationService($this->getAnnotationNamespacePrefix() . "\\MessageEndpoint\\SplitterOnMethod", "prod");

        $this->assertEquals(
            [
                AnnotatedMethod::create(
                    $annotation,
                    SplitterOnMethodExample::class,
                    "split",
                    [],
                    [$annotation]
                )
            ],
            $fileSystemAnnotationRegistrationService->findAnnotatedMethods(SomeHandlerAnnotation::class)
        );
    }

    public function test_ignoring_custom_not_found_annotations_on_class()
    {
        $annotation = new SomeHandlerAnnotation();
        $fileSystemAnnotationRegistrationService = $this->createAnnotationRegistrationService($this->getAnnotationNamespacePrefix() . "\\NotExisting", "prod");

        $this->assertEquals(
            [
                AnnotatedMethod::create(
                    $annotation,
                    NotExistingClassAttribute::class,
                    "test",
                    [],
                    [$annotation]
                )
            ],
            $fileSystemAnnotationRegistrationService->findAnnotatedMethods(SomeHandlerAnnotation::class)
        );
    }

    public function test_ignoring_custom_not_found_annotations_on_method()
    {
        $annotation = new SomeGatewayExample();
        $fileSystemAnnotationRegistrationService = $this->createAnnotationRegistrationService($this->getAnnotationNamespacePrefix() . "\\NotExisting", "prod");

        $this->assertEquals(
            [
                AnnotatedMethod::create(
                    $annotation,
                    NotExistingMethodAttribute::class,
                    "test",
                    [],
                    [$annotation]
                )
            ],
            $fileSystemAnnotationRegistrationService->findAnnotatedMethods(SomeGatewayExample::class)
        );
    }

    public function test_ignoring_custom_not_found_annotations_on_property()
    {
        $fileSystemAnnotationRegistrationService = $this->createAnnotationRegistrationService($this->getAnnotationNamespacePrefix() . "\\NotExisting", "prod");

        $this->assertEquals(
            [],
            $fileSystemAnnotationRegistrationService->getAnnotationsForProperty(NotExistingPropertyAttribute::class, "some")
        );
    }

    public function test_throwing_exception_if_class_is_registed_under_incorrect_namespace()
    {
        $this->expectException(\ReflectionException::class);

        new FileSystemAnnotationFinder(
            $this->getAnnotationResolver(),
            new AutoloadFileNamespaceParser(),
            self::ROOT_DIR,
            [
                "IncorrectAttribute"
            ],
            "test",
            ""
        );
    }

    public function test_not_including_classes_from_unregistered_namespace_when_using_namespace_inside()
    {
        new FileSystemAnnotationFinder(
            $this->getAnnotationResolver(),
            new AutoloadFileNamespaceParser(),
            self::ROOT_DIR,
            [
                "TestingNamespace"
            ],
            "test",
            ""
        );

        $this->assertTrue(true);
    }

    public function test_not_including_classes_from_unregistered_when_only_namespace_prefix_match()
    {
        new FileSystemAnnotationFinder(
            $this->getAnnotationResolver(),
            new AutoloadFileNamespaceParser(),
            self::ROOT_DIR,
            [
                "IncorrectAttribute\Testing"
            ],
            "test",
            ""
        );

        $this->assertTrue(true);
    }

    public function test_throwing_exception_if_given_catalog_to_load_and_no_namespaces_to_load()
    {
        $this->expectException(ConfigurationException::class);

        new FileSystemAnnotationFinder(
            $this->getAnnotationResolver(),
            InMemoryAutoloadNamespaceParser::createEmpty(),
            self::ROOT_DIR,
            [],
            "test",
            "src"
        );
    }

    public function test_throwing_exception_if_given_catalog_to_load_and_only_ecotone_namespace_defined_to_load()
    {
        $this->expectException(ConfigurationException::class);

        new FileSystemAnnotationFinder(
            $this->getAnnotationResolver(),
            InMemoryAutoloadNamespaceParser::createEmpty(),
            self::ROOT_DIR,
            [FileSystemAnnotationFinder::FRAMEWORK_NAMESPACE],
            "test",
            "src"
        );
    }

    private function createAnnotationRegistrationService(string $namespace, string $environmentName): AnnotationFinder
    {
        $fileSystemAnnotationRegistrationService = new FileSystemAnnotationFinder(
            $this->getAnnotationResolver(),
            new AutoloadFileNamespaceParser(),
            self::ROOT_DIR,
            [
                $namespace
            ],
            $environmentName,
            ""
        );

        return $fileSystemAnnotationRegistrationService;
    }
}