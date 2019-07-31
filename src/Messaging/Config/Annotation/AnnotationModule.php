<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Config\Annotation;

use Ecotone\Messaging\Config\Module;

/**
 * Interface AnnotationConfiguration
 * @package Ecotone\Messaging\Config\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface AnnotationModule extends Module
{
    /**
     * @param AnnotationRegistrationService $annotationRegistrationService
     * @return self
     */
    public static function create(AnnotationRegistrationService $annotationRegistrationService);
}