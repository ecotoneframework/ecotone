<?php


namespace Ecotone\Modelling\Config;


use Ecotone\Messaging\Annotation\EndpointAnnotation;
use Ecotone\Messaging\Annotation\ModuleAnnotation;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\AnnotationRegistration;
use Ecotone\Messaging\Config\Annotation\AnnotationRegistrationService;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\Bridge\BridgeBuilder;
use Ramsey\Uuid\Uuid;

/**
 * @ModuleAnnotation()
 */
class ModellingRouterToHandlerConnectorsModule implements AnnotationModule
{
    const MODULE_NAME = self::class;

    /**
     * @var array
     */
    private $mapping;

    private function __construct(array $mapping)
    {
        $this->mapping = $mapping;
    }

    /**
     * @inheritDoc
     */
    public static function create(AnnotationRegistrationService $annotationRegistrationService)
    {
        $mappings = [];
        foreach ([ModellingMessageRouterModule::getCommandBusByObjectMapping($annotationRegistrationService), ModellingMessageRouterModule::getCommandBusByNamesMapping($annotationRegistrationService), ModellingMessageRouterModule::getEventBusByObjectsMapping($annotationRegistrationService), ModellingMessageRouterModule::getEventBusByNamesMapping($annotationRegistrationService), ModellingMessageRouterModule::getQueryBusByObjectsMapping($annotationRegistrationService), ModellingMessageRouterModule::getQueryBusByNamesMapping($annotationRegistrationService)] as $mapping) {
            foreach ($mapping as $sourceChannel => $targetChannels) {
                $mappings[$sourceChannel] = array_merge($mappings[$sourceChannel] ?? [], $targetChannels);
            }
        }

        return new self($mappings);
    }

    /**
     * @inheritDoc
     */
    public function prepare(Configuration $configuration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService): void
    {
        foreach ($this->mapping as $sourceChannel => $targetChannels) {
            foreach ($targetChannels as $targetChannel) {
                $configuration->registerMessageHandler(
                    BridgeBuilder::create()
                        ->withEndpointId(Uuid::uuid4()->toString())
                        ->withInputChannelName($sourceChannel)
                        ->withOutputMessageChannel($targetChannel)
                );
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return self::MODULE_NAME;
    }

    /**
     * @inheritDoc
     */
    public function canHandle($extensionObject): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function getRelatedReferences(): array
    {
        return [];
    }
}