<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\Messaging\Unit\Config\Annotation;

use Doctrine\Common\Annotations\AnnotationReader;
use Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\Environment\ApplicationContextWithClassEnvironment;
use Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\Environment\ApplicationContextWithMethodEnvironmentExample;
use Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\Environment\ApplicationContextWithMethodMultipleEnvironmentsExample;
use Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\MessageEndpoint\Gateway\FileSystem\GatewayWithReplyChannelExample;
use Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\MessageEndpoint\Splitter\SplitterExample;
use SimplyCodedSoftware\Messaging\Annotation\ApplicationContext;
use SimplyCodedSoftware\Messaging\Annotation\EndpointAnnotation;
use SimplyCodedSoftware\Messaging\Annotation\Extension;
use SimplyCodedSoftware\Messaging\Annotation\Gateway\Gateway;
use SimplyCodedSoftware\Messaging\Annotation\Gateway\GatewayPayload;
use SimplyCodedSoftware\Messaging\Annotation\InputOutputEndpointAnnotation;
use SimplyCodedSoftware\Messaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\Messaging\Annotation\Parameter\Payload;
use SimplyCodedSoftware\Messaging\Annotation\Splitter;
use SimplyCodedSoftware\Messaging\Config\Annotation\AnnotationRegistration;
use SimplyCodedSoftware\Messaging\Config\Annotation\FileSystemAnnotationRegistrationService;
use SimplyCodedSoftware\Messaging\Config\ConfigurationException;
use Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\ApplicationContext\ApplicationContextExample;
use Test\SimplyCodedSoftware\Messaging\Unit\MessagingTest;


/**
 * Class FileSystemAnnotationRegistrationServiceTest
 * @package Test\SimplyCodedSoftware\Messaging\Unit\Config\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class FileSystemAnnotationRegistrationServiceIntegrationTest extends MessagingTest
{
    /**
     * @var FileSystemAnnotationRegistrationService
     */
    private static $annotationRegistrationService;

    /**
     * @throws ConfigurationException
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function setUp()
    {
        if (!self::$annotationRegistrationService) {
            self::$annotationRegistrationService = $this->createAnnotationRegistrationService("Test\SimplyCodedSoftware\Messaging\Fixture", "prod");
        }
    }

    public function test_retrieving_all_classes_with_annotation()
    {
        $classes = self::$annotationRegistrationService->getAllClassesWithAnnotation(ApplicationContext::class);

        $this->assertNotEmpty($classes, "File system class locator didn't find application context");
    }

    public function test_retrieving_annotation_for_class()
    {
        $this->assertEquals(
            new ApplicationContext(),
            self::$annotationRegistrationService->getAnnotationForClass(ApplicationContextExample::class, ApplicationContext::class)
        );
    }

    /**
     * @throws ConfigurationException
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_retrieving_annotation_registration_for_application_context()
    {
        $gatewayAnnotation = new Gateway();
        $gatewayAnnotation->requestChannel = "requestChannel";
        $messageToPayloadParameter = new GatewayPayload();
        $messageToPayloadParameter->parameterName = "orderId";
        $gatewayAnnotation->parameterConverters = [$messageToPayloadParameter];
        $gatewayAnnotation->requiredInterceptorNames = ["dbalTransaction"];

        $this->assertEquals(
            [
                AnnotationRegistration::create(
                    new MessageEndpoint(),
                    $gatewayAnnotation,
                    GatewayWithReplyChannelExample::class,
                    "buy"
                )
            ],
            $this->createAnnotationRegistrationService("Test\\SimplyCodedSoftware\\Messaging\\Fixture\\Annotation\\MessageEndpoint\Gateway\FileSystem", "prod")->findRegistrationsFor(MessageEndpoint::class, Gateway::class)
        );
    }

    public function test_retrieving_method_and_class_annotations()
    {
        $gatewayAnnotation = new Gateway();
        $gatewayAnnotation->requestChannel = "requestChannel";
        $messageToPayloadParameter = new GatewayPayload();
        $messageToPayloadParameter->parameterName = "orderId";
        $gatewayAnnotation->parameterConverters = [$messageToPayloadParameter];
        $gatewayAnnotation->requiredInterceptorNames = ["dbalTransaction"];

        $this->assertEquals(
            [
                $gatewayAnnotation
            ],
            $this->createAnnotationRegistrationService("Test\\SimplyCodedSoftware\\Messaging\\Fixture\\Annotation\\MessageEndpoint\Gateway\FileSystem", "prod")
                ->getAnnotationsForMethod(GatewayWithReplyChannelExample::class, "buy")
        );
    }

    public function test_retrieving_class_annotations()
    {
        $this->assertEquals(
            [
                new MessageEndpoint()
            ],
            $this->createAnnotationRegistrationService("Test\\SimplyCodedSoftware\\Messaging\\Fixture\\Annotation\\MessageEndpoint\Gateway\FileSystem", "prod")
                ->getAnnotationsForClass(GatewayWithReplyChannelExample::class)
        );
    }

    /**
     * @throws ConfigurationException
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \Exception
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_retrieving_for_specific_environment()
    {
        $fileSystemAnnotationRegistrationService = $this->createAnnotationRegistrationService("Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\Environment", "dev");
        $this->assertEquals(
            [
                $this->createAnnotationRegistration(new ApplicationContext(), new Extension(), ApplicationContextWithMethodEnvironmentExample::class, "configSingleEnvironment"),
                $this->createAnnotationRegistration(new ApplicationContext(), new Extension(), ApplicationContextWithMethodMultipleEnvironmentsExample::class, "configMultipleEnvironments")
            ],
            $fileSystemAnnotationRegistrationService->findRegistrationsFor(ApplicationContext::class, Extension::class)
        );


        $fileSystemAnnotationRegistrationService = $this->createAnnotationRegistrationService("Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\Environment", "test");
        $this->assertEquals(
            [
                $this->createAnnotationRegistration(new ApplicationContext(), new Extension(), ApplicationContextWithMethodMultipleEnvironmentsExample::class, "configMultipleEnvironments")
            ],
            $fileSystemAnnotationRegistrationService->findRegistrationsFor(ApplicationContext::class, Extension::class)
        );

        $fileSystemAnnotationRegistrationService = $this->createAnnotationRegistrationService("Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\Environment", "prod");
        $this->assertEquals(
            [
                $this->createAnnotationRegistration(new ApplicationContext(), new Extension(), ApplicationContextWithClassEnvironment::class, "someAction"),
                $this->createAnnotationRegistration(new ApplicationContext(), new Extension(), ApplicationContextWithMethodMultipleEnvironmentsExample::class, "configMultipleEnvironments")
            ],
            $fileSystemAnnotationRegistrationService->findRegistrationsFor(ApplicationContext::class, Extension::class)
        );
    }

    /**
     * @param $classAnnotation
     * @param $methodAnnotation
     * @param string $className
     * @param string $methodName
     * @return AnnotationRegistration
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    private function createAnnotationRegistration($classAnnotation, $methodAnnotation, string $className, string $methodName) : AnnotationRegistration
    {
        return AnnotationRegistration::create(
            $classAnnotation,
            $methodAnnotation,
            $className,
            $methodName
        );
    }

    /**
     * @throws ConfigurationException
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \Exception
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_retrieving_subclass_annotation()
    {
        $annotation                               = new Splitter();
        $annotation->endpointId                   = "testId";
        $annotation->inputChannelName             = "inputChannel";
        $annotation->outputChannelName            = "outputChannel";
        $annotation->requiredInterceptorNames     = ["someReference"];
        $messageToPayloadParameter                = new Payload();
        $messageToPayloadParameter->parameterName = "payload";
        $annotation->parameterConverters          = [$messageToPayloadParameter];

        $fileSystemAnnotationRegistrationService = $this->createAnnotationRegistrationService("Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\MessageEndpoint\Splitter", "prod");

        $this->assertEquals(
            [
                AnnotationRegistration::create(
                    new MessageEndpoint(),
                    $annotation,
                    SplitterExample::class,
                    "split"
                )
            ],
            $fileSystemAnnotationRegistrationService->findRegistrationsFor(MessageEndpoint::class, EndpointAnnotation::class)
        );
    }

    /**
     * @throws ConfigurationException
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_retrieving_with_random_endpoint_id_if_not_defined()
    {
        $fileSystemAnnotationRegistrationService = $this->createAnnotationRegistrationService("Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\MessageEndpoint\NoEndpointIdSplitter", "prod");

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

    /**
     * @param string $namespace
     * @param string $environmentName
     * @return FileSystemAnnotationRegistrationService
     * @throws ConfigurationException
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    private function createAnnotationRegistrationService(string $namespace, string $environmentName): FileSystemAnnotationRegistrationService
    {
        $fileSystemAnnotationRegistrationService = new FileSystemAnnotationRegistrationService(
            new AnnotationReader(),
            self::ROOT_DIR,
            [
                $namespace
            ],
            $environmentName,
            false
        );
        return $fileSystemAnnotationRegistrationService;
    }
}