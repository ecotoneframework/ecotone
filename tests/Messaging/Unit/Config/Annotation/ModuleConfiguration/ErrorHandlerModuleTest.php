<?php

namespace Test\Ecotone\Messaging\Unit\Config\Annotation\ModuleConfiguration;

use Ecotone\AnnotationFinder\InMemory\InMemoryAnnotationFinder;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ErrorHandlerModule;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Recoverability\ErrorHandlerConfiguration;

/**
 * @internal
 */
class ErrorHandlerModuleTest extends AnnotationConfigurationTest
{
    public function test_registering_module_with_default_error_handling()
    {
        $errorHandlerModuleWithCustom = $this->createMessagingSystemConfiguration();
        ErrorHandlerModule::create(InMemoryAnnotationFinder::createEmpty(),InterfaceToCallRegistry::createEmpty())
            ->prepare($errorHandlerModuleWithCustom, [
                ErrorHandlerConfiguration::createDefault()
            ], ModuleReferenceSearchService::createEmpty(), InterfaceToCallRegistry::createEmpty());

        $errorHandlerModuleWithDefault = $this->createMessagingSystemConfiguration();
        ErrorHandlerModule::create(InMemoryAnnotationFinder::createEmpty(),InterfaceToCallRegistry::createEmpty())
            ->prepare($errorHandlerModuleWithDefault, [

            ], ModuleReferenceSearchService::createEmpty(), InterfaceToCallRegistry::createEmpty());

        $this->assertEquals(
            $errorHandlerModuleWithCustom,
            $errorHandlerModuleWithDefault
        );
    }
}
