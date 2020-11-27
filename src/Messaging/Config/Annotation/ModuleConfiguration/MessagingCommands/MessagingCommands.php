<?php


namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration\MessagingCommands;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Messaging\Annotation\ModuleAnnotation;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ConsoleCommandModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\NoExternalConfigurationModule;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;

#[ModuleAnnotation]
class MessagingCommands extends NoExternalConfigurationModule implements AnnotationModule
{
    public static function create(AnnotationFinder $annotationRegistrationService): static
    {
        return new self();
    }

    public function prepare(Configuration $configuration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService): void
    {
        $this->registerOneTimeCommand("runAsynchronousEndpoint", "ecotone:run", $configuration);
        $this->registerOneTimeCommand("listAsynchronousEndpoints", "ecotone:list", $configuration);
    }

    public function canHandle($extensionObject): bool
    {
        return false;
    }

    private function registerOneTimeCommand(string $methodName, string $commandName, Configuration $configuration): void
    {
        list($messageHandlerBuilder, $oneTimeCommandConfiguration) = ConsoleCommandModule::prepareConsoleCommandForDirectObject(
            new MessagingBaseCommand(), $methodName, $commandName, false
        );
        $configuration
            ->registerMessageHandler($messageHandlerBuilder)
            ->registerConsoleCommand($oneTimeCommandConfiguration);
    }
}