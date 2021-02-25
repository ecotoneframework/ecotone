<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration;

use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Handler\ReferenceSearchService;

/**
 * Class NoExternalConfigurationModule
 * @package Ecotone\Messaging\Config\Annotation\ModuleConfiguration
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
abstract class NoExternalConfigurationModule implements AnnotationModule
{
    /**
     * @inheritDoc
     */
    public function getRelatedReferences(): array
    {
        return [];
    }

    public function getModuleExtensions(array $serviceExtensions): array
    {
        return [];
    }
}