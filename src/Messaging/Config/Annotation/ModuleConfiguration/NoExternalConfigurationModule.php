<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration;

use Ecotone\Messaging\Config\Annotation\AnnotationModule;

/**
 * Class NoExternalConfigurationModule
 * @package Ecotone\Messaging\Config\Annotation\ModuleConfiguration
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
abstract class NoExternalConfigurationModule implements AnnotationModule
{
    public function getModuleExtensions(array $serviceExtensions): array
    {
        return [];
    }
}
