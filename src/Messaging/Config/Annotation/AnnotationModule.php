<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Config\Annotation;

use SimplyCodedSoftware\Messaging\Config\Module;

/**
 * Interface AnnotationConfiguration
 * @package SimplyCodedSoftware\Messaging\Config\Annotation
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