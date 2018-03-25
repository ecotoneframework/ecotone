<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Config\Annotation;

use Doctrine\Common\Annotations\AnnotationReader;
use Fixture\Annotation\ApplicationContext\ApplicationContextExample;
use Fixture\Annotation\MessageEndpoint\Gateway\GatewayWithReplyChannelExample;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\ApplicationContextAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\GatewayAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageEndpointAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageToParameter\MessageToPayloadParameterAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\ModuleAnnotation;
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
        $classes = self::$annotationRegistrationService->getAllClassesWithAnnotation(ApplicationContextAnnotation::class);

        $this->assertCount(2, $classes, "File system class locator didn't find application context");
    }

    public function test_retrieving_class_annotations()
    {
        $this->assertEquals(
            new ApplicationContextAnnotation(),
            self::$annotationRegistrationService->getAnnotationForClass(ApplicationContextExample::class, ApplicationContextAnnotation::class)
        );
    }

    /**
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_retrieving_annotation_registration_for_application_context()
    {
        $gatewayAnnotation = new GatewayAnnotation();
        $gatewayAnnotation->requestChannel = "requestChannel";
        $messageToPayloadParameter = new MessageToPayloadParameterAnnotation();
        $messageToPayloadParameter->parameterName = "orderId";
        $gatewayAnnotation->parameterConverters = [$messageToPayloadParameter];

        $this->assertEquals(
            [
                AnnotationRegistration::create(
                    new MessageEndpointAnnotation(),
                    $gatewayAnnotation,
                    GatewayWithReplyChannelExample::class,
                    "buy"
                )
            ],
            self::$annotationRegistrationService->findRegistrationsFor(MessageEndpointAnnotation::class, GatewayAnnotation::class)
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