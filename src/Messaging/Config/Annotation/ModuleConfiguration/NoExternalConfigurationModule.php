<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Config\Annotation\ModuleConfiguration;

use SimplyCodedSoftware\Messaging\Config\Annotation\AnnotationModule;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;

/**
 * Class NoExternalConfigurationModule
 * @package SimplyCodedSoftware\Messaging\Config\Annotation\ModuleConfiguration
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
abstract class NoExternalConfigurationModule implements AnnotationModule
{
    /**
     * @inheritDoc
     */
    public function getConfigurationVariables(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function afterConfigure(ReferenceSearchService $referenceSearchService): void
    {
        return;
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferences(): array
    {
        return [];
    }
}