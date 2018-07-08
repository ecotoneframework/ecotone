<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Config\Annotation;

use SimplyCodedSoftware\IntegrationMessaging\Config\Module;

/**
 * Interface AnnotationConfiguration
 * @package SimplyCodedSoftware\IntegrationMessaging\Config\Annotation
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