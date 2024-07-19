<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration;

use Ecotone\Messaging\Config\Annotation\AnnotationModule;

/**
 * Class NoExternalConfigurationModule
 * @package Ecotone\Messaging\Config\Annotation\ModuleConfiguration
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
abstract class NoExternalConfigurationModule implements AnnotationModule
{
    public function getModuleExtensions(array $serviceExtensions): array
    {
        return [];
    }
}
