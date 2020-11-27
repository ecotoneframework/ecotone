<?php


namespace Test\Ecotone\AnnotationFinder\Unit;


use Ecotone\AnnotationFinder\AnnotatedDefinition;
use Ecotone\AnnotationFinder\AnnotatedMethod;
use Ecotone\AnnotationFinder\Annotation\Environment;
use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\AnnotationFinder\AnnotationResolver;
use Ecotone\AnnotationFinder\ConfigurationException;
use Ecotone\AnnotationFinder\FileSystem\AutoloadFileNamespaceParser;
use Ecotone\AnnotationFinder\FileSystem\FileSystemAnnotationFinder;
use Ecotone\AnnotationFinder\FileSystem\InMemoryAutoloadNamespaceParser;
use Ecotone\AnnotationFinder\InMemory\InMemoryAnnotationFinder;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\Annotation\ApplicationContext;
use Test\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\Annotation\EndpointAnnotationExample;
use Test\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\Annotation\Extension;
use Test\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\Annotation\MessageEndpoint;
use Test\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\Annotation\SomeGatewayExample;
use Test\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\Annotation\SomeHandlerAnnotation;
use Test\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\Environment\SystemContextWithClassEnvironment;
use Test\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\Environment\SystemContextWithMethodEnvironmentExample;
use Test\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\Environment\SystemContextWithMethodMultipleEnvironmentsExample;
use Test\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\MessageEndpoint\Gateway\FileSystem\GatewayWithReplyChannelExample;
use Test\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\MessageEndpoint\Splitter\SplitterExample;
use Test\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\MessageEndpoint\SplitterOnMethod\SplitterOnMethodExample;

class InMemoryAttributeAnnotationFinderTest extends TestCase
{
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
            $this->createAnnotationRegistrationService([GatewayWithReplyChannelExample::class])
                ->findCombined(MessageEndpoint::class, SomeGatewayExample::class)
        );
    }

    public function test_retrieving_method_annotations()
    {
        $gatewayAnnotation = new SomeGatewayExample();

        $this->assertEquals(
            [
                $gatewayAnnotation
            ],
            $this->createAnnotationRegistrationService([GatewayWithReplyChannelExample::class])
                ->getAnnotationsForMethod(GatewayWithReplyChannelExample::class, "buy")
        );
    }

    public function test_retrieving_class_annotations()
    {
        $this->assertEquals(
            [
                new MessageEndpoint()
            ],
            $this->createAnnotationRegistrationService([GatewayWithReplyChannelExample::class])
                ->getAnnotationsForClass(GatewayWithReplyChannelExample::class)
        );
    }

    public function test_finding_annotated_classes()
    {
        $this->assertEquals(
            [
                GatewayWithReplyChannelExample::class
            ],
            $this->createAnnotationRegistrationService([GatewayWithReplyChannelExample::class])
                ->findAnnotatedClasses(MessageEndpoint::class)
        );
    }

    public function test_retrieving_by_only_method_annotation()
    {
        $annotation = new SomeHandlerAnnotation();

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
            $this->createAnnotationRegistrationService([SplitterOnMethodExample::class])
                ->findAnnotatedMethods(SomeHandlerAnnotation::class)
        );
    }

    private function createAnnotationRegistrationService(array $classes): AnnotationFinder
    {
        return InMemoryAnnotationFinder::createFrom($classes);
    }
}