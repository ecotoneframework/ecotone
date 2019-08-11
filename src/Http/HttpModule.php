<?php
declare(strict_types=1);

namespace Ecotone\Http;


use Ecotone\Messaging\Annotation\ModuleAnnotation;
use Ecotone\Messaging\Config\Annotation\AnnotationRegistrationService;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\NoExternalConfigurationModule;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;

/**
 * Class HttpModule
 * @package Ecotone\Http
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @ModuleAnnotation()
 */
class HttpModule extends NoExternalConfigurationModule
{
    /**
     * @inheritDoc
     */
    public static function create(AnnotationRegistrationService $annotationRegistrationService)
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return "httpModule";
    }

    /**
     * @inheritDoc
     */
    public function prepare(Configuration $configuration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService): void
    {
        $moduleReferenceSearchService->store("psrHttpMessageConverter", PsrHttpMessageConverter::create());
        $configuration->registerMessageConverter("psrHttpMessageConverter");
    }

    /**
     * @inheritDoc
     */
    public function canHandle($extensionObject): bool
    {
        return false;
    }
}