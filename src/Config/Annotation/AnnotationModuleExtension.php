<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Config\Annotation;

use SimplyCodedSoftware\IntegrationMessaging\Config\ModuleExtension;

/**
 * Interface AnnotationModuleExtension
 * @package SimplyCodedSoftware\IntegrationMessaging\Config\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface AnnotationModuleExtension extends ModuleExtension
{
    /**
     * @param AnnotationRegistrationService $annotationRegistrationService
     * @return self
     */
    public static function create(AnnotationRegistrationService $annotationRegistrationService);
}