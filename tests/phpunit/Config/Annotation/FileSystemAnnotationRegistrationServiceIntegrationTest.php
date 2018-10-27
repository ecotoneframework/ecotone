<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Config\Annotation;

use Doctrine\Common\Annotations\AnnotationReader;
use Fixture\Annotation\ApplicationContext\ApplicationContextExample;
use Fixture\Annotation\MessageEndpoint\Gateway\FileSystem\GatewayWithReplyChannelExample;
use Fixture\Annotation\MessageEndpoint\Splitter\SplitterExample;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\ApplicationContext;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\EndpointAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Gateway\Gateway;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Gateway\GatewayPayload;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\InputOutputEndpointAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Parameter\Payload;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Splitter;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationRegistration;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\FileSystemAnnotationRegistrationService;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationException;
use Test\SimplyCodedSoftware\IntegrationMessaging\MessagingTest;


/**
 * Class FileSystemAnnotationRegistrationServiceTest
 * @package Test\SimplyCodedSoftware\IntegrationMessaging\Config\Annotation
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
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function setUp()
    {
        if (!self::$annotationRegistrationService) {
            self::$annotationRegistrationService = $this->createAnnotationRegistrationService("Fixture");
        }
    }

    public function test_retrieving_all_classes_with_annotation()
    {
        $classes = self::$annotationRegistrationService->getAllClassesWithAnnotation(ApplicationContext::class);

        $this->assertNotEmpty($classes, "File system class locator didn't find application context");
    }

    public function test_retrieving_class_annotations()
    {
        $this->assertEquals(
            new ApplicationContext(),
            self::$annotationRegistrationService->getAnnotationForClass(ApplicationContextExample::class, ApplicationContext::class)
        );
    }

    /**
     * @throws ConfigurationException
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_retrieving_annotation_registration_for_application_context()
    {
        $gatewayAnnotation = new Gateway();
        $gatewayAnnotation->requestChannel = "requestChannel";
        $messageToPayloadParameter = new GatewayPayload();
        $messageToPayloadParameter->parameterName = "orderId";
        $gatewayAnnotation->parameterConverters = [$messageToPayloadParameter];
        $gatewayAnnotation->transactionFactories = ["dbalTransaction"];

        $this->assertEquals(
            [
                AnnotationRegistration::create(
                    new MessageEndpoint(),
                    $gatewayAnnotation,
                    GatewayWithReplyChannelExample::class,
                    "buy"
                )
            ],
            $this->createAnnotationRegistrationService("Fixture\\Annotation\\MessageEndpoint\Gateway\FileSystem")->findRegistrationsFor(MessageEndpoint::class, Gateway::class)
        );
    }

    /**
     * @throws ConfigurationException
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \Exception
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_retrieving_subclass_annotation()
    {
        $annotation = new Splitter();
        $annotation->endpointId = "testId";
        $annotation->inputChannelName = "inputChannel";
        $annotation->outputChannelName = "outputChannel";
        $messageToPayloadParameter = new Payload();
        $messageToPayloadParameter->parameterName = "payload";
        $annotation->parameterConverters = [$messageToPayloadParameter];

        $fileSystemAnnotationRegistrationService = $this->createAnnotationRegistrationService("Fixture\Annotation\MessageEndpoint\Splitter");

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
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_retrieving_with_random_endpoint_id_if_not_defined()
    {
        $fileSystemAnnotationRegistrationService = $this->createAnnotationRegistrationService("Fixture\Annotation\MessageEndpoint\NoEndpointIdSplitter");

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
            $annotationForMethodRetrievedAsEndpoint->endpointId,
            $annotationForMethodRetrievedAsInputOutput->endpointId
        );
    }

    /**
     * @param string $namespace
     * @return FileSystemAnnotationRegistrationService
     * @throws ConfigurationException
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    private function createAnnotationRegistrationService(string $namespace): FileSystemAnnotationRegistrationService
    {
        $fileSystemAnnotationRegistrationService = new FileSystemAnnotationRegistrationService(
            new AnnotationReader(),
            self::ROOT_DIR,
            [
                $namespace
            ],
            false
        );
        return $fileSystemAnnotationRegistrationService;
    }
}