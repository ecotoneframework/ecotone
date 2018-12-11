<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\ModuleConfiguration;

use SimplyCodedSoftware\Messaging\Annotation\ModuleAnnotation;
use SimplyCodedSoftware\Messaging\Config\ConfigurableReferenceSearchService;
use SimplyCodedSoftware\Messaging\Config\Configuration;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;

/**
 * Class ExampleModuleConfiguration
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @ModuleAnnotation()
 */
class ExampleModuleConfiguration implements \SimplyCodedSoftware\Messaging\Config\Annotation\AnnotationModule
{
    /**
     * @var array
     */
    private $extensionObjects;

    private function __construct(array $extensionObjects)
    {
        $this->extensionObjects = $extensionObjects;
    }

    public static function createEmpty(): self
    {
        return new self([]);
    }

    /**
     * @param array $extensionObjects
     * @return ExampleModuleConfiguration
     */
    public static function createWithExtensions(array $extensionObjects): self
    {
        return new self($extensionObjects);
    }

    /**
     * @inheritDoc
     */
    public static function create(\SimplyCodedSoftware\Messaging\Config\Annotation\AnnotationRegistrationService $annotationRegistrationService): \SimplyCodedSoftware\Messaging\Config\Annotation\AnnotationModule
    {
        return new self([]);
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return "exampleModule";
    }

    /**
     * @inheritDoc
     */
    public function prepare(Configuration $configuration, array $extensionObjects, ConfigurableReferenceSearchService $configurableReferenceSearchService): void
    {
        $this->extensionObjects = $extensionObjects;

        return;
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferences(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function canHandle($extensionObject): bool
    {
        return $extensionObject instanceof \stdClass;
    }

    /**
     * @inheritDoc
     */
    public function afterConfigure(ReferenceSearchService $referenceSearchService): void
    {
        // TODO: Implement registerWithin() method.
    }


}