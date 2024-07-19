<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Config\Annotation;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Messaging\Config\Module;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;

/**
 * Interface AnnotationConfiguration
 * @package Ecotone\Messaging\Config\Annotation
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
interface AnnotationModule extends Module
{
    public static function create(AnnotationFinder $annotationRegistrationService, InterfaceToCallRegistry $interfaceToCallRegistry): static;
}
