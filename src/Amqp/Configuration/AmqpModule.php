<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Amqp\Configuration;

use SimplyCodedSoftware\Messaging\Config\Annotation\AnnotationModule;
use SimplyCodedSoftware\Messaging\Config\Annotation\AnnotationRegistrationService;
use SimplyCodedSoftware\Messaging\Config\ConfigurableReferenceSearchService;
use SimplyCodedSoftware\Messaging\Config\Configuration;
use SimplyCodedSoftware\Messaging\Config\RequiredReference;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;

/**
 * Class AmqpModule
 * @package SimplyCodedSoftware\Amqp\Configuration
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AmqpModule implements AnnotationModule
{
    /**
     * @inheritDoc
     */
    public static function create(AnnotationRegistrationService $annotationRegistrationService)
    {
        // TODO: Implement create() method.
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return "amqpModule";
    }

    /**
     * @inheritDoc
     */
    public function prepare(Configuration $configuration, array $extensionObjects, ConfigurableReferenceSearchService $configurableReferenceSearchService): void
    {

    }

    /**
     * @inheritDoc
     */
    public function canHandle($extensionObject): bool
    {
        // TODO: Implement canHandle() method.
    }

    /**
     * @inheritDoc
     */
    public function afterConfigure(ReferenceSearchService $referenceSearchService): void
    {
        // TODO: Implement afterConfigure() method.
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferences(): array
    {
        return [];
    }
}