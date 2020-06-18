<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Config\Annotation;

use Doctrine\Common\Annotations\AnnotationReader;
use Ecotone\Messaging\Annotation\ApplicationContext;
use Ecotone\Messaging\Annotation\EndpointAnnotation;
use Ecotone\Messaging\Annotation\Environment;
use Ecotone\Messaging\Annotation\Extension;
use Ecotone\Messaging\Annotation\MessageGateway;
use Ecotone\Messaging\Annotation\Gateway\GatewayPayload;
use Ecotone\Messaging\Annotation\InputOutputEndpointAnnotation;
use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Annotation\Parameter\Payload;
use Ecotone\Messaging\Annotation\Splitter;
use Ecotone\Messaging\Config\Annotation\AnnotationRegistration;
use Ecotone\Messaging\Config\Annotation\AutoloadFileNamespaceParser;
use Ecotone\Messaging\Config\Annotation\FileSystemAnnotationRegistrationService;
use Ecotone\Messaging\Config\Annotation\InMemoryAutoloadNamespaceParser;
use Ecotone\Messaging\Config\ConfigurationException;
use Test\Ecotone\Messaging\Fixture\Annotation\ApplicationContext\ApplicationContextExample;
use Test\Ecotone\Messaging\Fixture\Annotation\Environment\ApplicationContextWithClassEnvironment;
use Test\Ecotone\Messaging\Fixture\Annotation\Environment\ApplicationContextWithMethodEnvironmentExample;
use Test\Ecotone\Messaging\Fixture\Annotation\Environment\ApplicationContextWithMethodMultipleEnvironmentsExample;
use Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Gateway\FileSystem\GatewayWithReplyChannelExample;
use Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Splitter\SplitterExample;
use Test\Ecotone\Messaging\Unit\MessagingTest;


/**
 * Class FileSystemAnnotationRegistrationServiceTest
 * @package Test\Ecotone\Messaging\Unit\Config\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class FileSystemAnnotationRegistrationServiceIntegrationTest extends MessagingTest
{
    /**
     * @var FileSystemAnnotationRegistrationService
     */
    private static $annotationRegistrationService;

    /**
     * @requires PHP >= 7.4
     */
    public function test_retrieving_all_classes_with_annotation()
    {
        $classes = $this->getAnnotationRegistrationService()->getAllClassesWithAnnotation(ApplicationContext::class);

        $this->assertNotEmpty($classes, "File system class locator didn't find application context");
    }

    /**
     * @requires PHP >= 7.4
     */
    public function test_retrieving_annotation_for_class()
    {
        $this->assertEquals(
            new ApplicationContext(),
            $this->getAnnotationRegistrationService()->getAnnotationForClass(ApplicationContextExample::class, ApplicationContext::class)
        );
    }

    /**
     * @throws ConfigurationException
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_retrieving_annotation_registration_for_application_context()
    {
        $gatewayAnnotation = new MessageGateway();
        $gatewayAnnotation->requestChannel = "requestChannel";
        $messageToPayloadParameter = new Payload();
        $messageToPayloadParameter->parameterName = "orderId";
        $gatewayAnnotation->parameterConverters = [$messageToPayloadParameter];
        $gatewayAnnotation->requiredInterceptorNames = ["dbalTransaction"];

        $messageEndpoint = new MessageEndpoint();
        $this->assertEquals(
            [
                AnnotationRegistration::create(
                    $messageEndpoint,
                    $gatewayAnnotation,
                    GatewayWithReplyChannelExample::class,
                    "buy",
                    [$messageEndpoint],
                    [$gatewayAnnotation]
                )
            ],
            $this->createAnnotationRegistrationService("Test\\Ecotone\\Messaging\\Fixture\\Annotation\\MessageEndpoint\Gateway\FileSystem", "prod")->findRegistrationsFor(MessageEndpoint::class, MessageGateway::class)
        );
    }

    public function test_retrieving_method_and_class_annotations()
    {
        $gatewayAnnotation = new MessageGateway();
        $gatewayAnnotation->requestChannel = "requestChannel";
        $messageToPayloadParameter = new Payload();
        $messageToPayloadParameter->parameterName = "orderId";
        $gatewayAnnotation->parameterConverters = [$messageToPayloadParameter];
        $gatewayAnnotation->requiredInterceptorNames = ["dbalTransaction"];

        $this->assertEquals(
            [
                $gatewayAnnotation
            ],
            $this->createAnnotationRegistrationService("Test\\Ecotone\\Messaging\\Fixture\\Annotation\\MessageEndpoint\Gateway\FileSystem", "prod")
                ->getAnnotationsForMethod(GatewayWithReplyChannelExample::class, "buy")
        );
    }

    public function test_retrieving_class_annotations()
    {
        $this->assertEquals(
            [
                new MessageEndpoint()
            ],
            $this->createAnnotationRegistrationService("Test\\Ecotone\\Messaging\\Fixture\\Annotation\\MessageEndpoint\Gateway\FileSystem", "prod")
                ->getAnnotationsForClass(GatewayWithReplyChannelExample::class)
        );
    }

    /**
     * @throws ConfigurationException
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \Exception
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_retrieving_for_specific_environment()
    {
        $fileSystemAnnotationRegistrationService = $this->createAnnotationRegistrationService("Test\Ecotone\Messaging\Fixture\Annotation\Environment", "dev");
        $devEnvironment                             = new Environment();
        $devEnvironment->names = ["dev"];
        $prodDevEnvironment = new Environment();
        $prodDevEnvironment->names = ["prod", "dev"];
        $prodEnvironment = new Environment();
        $prodEnvironment->names = ["prod"];
        $allEnvironment = new Environment();
        $allEnvironment->names = ["dev", "prod", "test"];
        $methodAnnotation = new Extension();
        $applicationContext = new ApplicationContext();

        $this->assertEquals(
            [
                $this->createAnnotationRegistration($applicationContext, $methodAnnotation, ApplicationContextWithMethodEnvironmentExample::class, "configSingleEnvironment", [$applicationContext, $prodDevEnvironment],[$methodAnnotation, $devEnvironment]),
                $this->createAnnotationRegistration($applicationContext, $methodAnnotation, ApplicationContextWithMethodMultipleEnvironmentsExample::class, "configMultipleEnvironments", [$applicationContext],[$methodAnnotation, $allEnvironment])
            ],
            $fileSystemAnnotationRegistrationService->findRegistrationsFor(ApplicationContext::class, Extension::class)
        );


        $fileSystemAnnotationRegistrationService = $this->createAnnotationRegistrationService("Test\Ecotone\Messaging\Fixture\Annotation\Environment", "test");
        $this->assertEquals(
            [
                $this->createAnnotationRegistration($applicationContext, $methodAnnotation, ApplicationContextWithMethodMultipleEnvironmentsExample::class, "configMultipleEnvironments", [$applicationContext], [$methodAnnotation, $allEnvironment])],
            $fileSystemAnnotationRegistrationService->findRegistrationsFor(ApplicationContext::class, Extension::class)
        );

        $fileSystemAnnotationRegistrationService = $this->createAnnotationRegistrationService("Test\Ecotone\Messaging\Fixture\Annotation\Environment", "prod");
        $this->assertEquals(
            [
                $this->createAnnotationRegistration($applicationContext, $methodAnnotation, ApplicationContextWithClassEnvironment::class, "someAction", [$applicationContext, $prodEnvironment], [$methodAnnotation]),
                $this->createAnnotationRegistration($applicationContext, $methodAnnotation, ApplicationContextWithMethodMultipleEnvironmentsExample::class, "configMultipleEnvironments", [$applicationContext], [$methodAnnotation, $allEnvironment])
            ],
            $fileSystemAnnotationRegistrationService->findRegistrationsFor(ApplicationContext::class, Extension::class)
        );
    }

    private function createAnnotationRegistration(object $classAnnotation, object $methodAnnotation, string $className, string $methodName, array $classAnnotations, array $methodAnnotations) : AnnotationRegistration
    {
        return AnnotationRegistration::create(
            $classAnnotation,
            $methodAnnotation,
            $className,
            $methodName,
            $classAnnotations,
            $methodAnnotations
        );
    }

    /**
     * @throws ConfigurationException
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \Exception
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_retrieving_subclass_annotation()
    {
        $annotation                               = new Splitter(["endpointId" => "testId"]);
        $annotation->inputChannelName             = "inputChannel";
        $annotation->outputChannelName            = "outputChannel";
        $annotation->requiredInterceptorNames     = ["someReference"];
        $messageToPayloadParameter                = new Payload();
        $messageToPayloadParameter->parameterName = "payload";
        $annotation->parameterConverters          = [$messageToPayloadParameter];

        $fileSystemAnnotationRegistrationService = $this->createAnnotationRegistrationService("Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Splitter", "prod");

        $annotationForClass = new MessageEndpoint();
        $this->assertEquals(
            [
                AnnotationRegistration::create(
                    $annotationForClass,
                    $annotation,
                    SplitterExample::class,
                    "split",
                    [$annotationForClass],
                    [$annotation]
                )
            ],
            $fileSystemAnnotationRegistrationService->findRegistrationsFor(MessageEndpoint::class, EndpointAnnotation::class)
        );
    }

    /**
     * @throws ConfigurationException
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_retrieving_with_random_endpoint_id_if_not_defined()
    {
        $fileSystemAnnotationRegistrationService = $this->createAnnotationRegistrationService("Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\NoEndpointIdSplitter", "prod");

        /** @var AnnotationRegistration[] $annotationRegistrations */
        $annotationRegistrations = $fileSystemAnnotationRegistrationService->findRegistrationsFor(MessageEndpoint::class, EndpointAnnotation::class);
        /** @var Splitter $annotationForMethod */
        $annotationForMethodRetrievedAsEndpoint = $annotationRegistrations[0]->getAnnotationForMethod();

        $this->assertNotEmpty($annotationForMethodRetrievedAsEndpoint->endpointId);

        /** @var AnnotationRegistration[] $annotationRegistrations */
        $annotationRegistrations = $fileSystemAnnotationRegistrationService->findRegistrationsFor(MessageEndpoint::class, InputOutputEndpointAnnotation::class);
        /** @var Splitter $annotationForMethod */
        $annotationForMethodRetrievedAsInputOutput = $annotationRegistrations[0]->getAnnotationForMethod();

        $this->assertEquals(
            $annotationForMethodRetrievedAsEndpoint,
            $annotationForMethodRetrievedAsInputOutput
        );
    }

    public function test_throwing_exception_if_class_is_registed_under_incorrect_namespace()
    {
        $this->expectException(\ReflectionException::class);

        new FileSystemAnnotationRegistrationService(
            new AnnotationReader(),
            new AutoloadFileNamespaceParser(),
            self::ROOT_DIR,
            [
                "Incorrect"
            ],
            "test",
            ""
        );
    }

    public function test_not_including_classes_from_unregistered_namespace_when_using_namespace_inside()
    {
        new FileSystemAnnotationRegistrationService(
            new AnnotationReader(),
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
        new FileSystemAnnotationRegistrationService(
            new AnnotationReader(),
            new AutoloadFileNamespaceParser(),
            self::ROOT_DIR,
            [
                "Incorrect\Testing"
            ],
            "test",
            ""
        );

        $this->assertTrue(true);
    }

    public function test_throwing_exception_if_given_catalog_to_load_and_no_namespaces_to_load()
    {
        $this->expectException(ConfigurationException::class);

        new FileSystemAnnotationRegistrationService(
            new AnnotationReader(),
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

        new FileSystemAnnotationRegistrationService(
            new AnnotationReader(),
            InMemoryAutoloadNamespaceParser::createEmpty(),
            self::ROOT_DIR,
            [FileSystemAnnotationRegistrationService::FRAMEWORK_NAMESPACE],
            "test",
            "src"
        );
    }

    /**
     * @param string $namespace
     * @param string $environmentName
     * @return FileSystemAnnotationRegistrationService
     * @throws ConfigurationException
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \Ecotone\Messaging\MessagingException
     */
    private function createAnnotationRegistrationService(string $namespace, string $environmentName): FileSystemAnnotationRegistrationService
    {
        $fileSystemAnnotationRegistrationService = new FileSystemAnnotationRegistrationService(
            new AnnotationReader(),
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

    /**
     * @return FileSystemAnnotationRegistrationService
     * @throws ConfigurationException
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \Ecotone\Messaging\MessagingException
     */
    private function getAnnotationRegistrationService(): FileSystemAnnotationRegistrationService
    {
        if (!self::$annotationRegistrationService) {
            self::$annotationRegistrationService = $this->createAnnotationRegistrationService("Test\Ecotone\Messaging\Fixture", "prod");
        }

        return self::$annotationRegistrationService;
    }
}