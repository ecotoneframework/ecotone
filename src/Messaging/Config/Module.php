<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Config;

use Ecotone\Messaging\Handler\InterfaceToCallRegistry;

/**
 * Interface ExternalConfiguration
 * @package Ecotone\Messaging\Config
 * @author  Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
interface Module
{
    /**
     * In here you can register all message handlers, gateways, message channels
     *
     * @param object[]                     $extensionObjects
     * @param ModuleReferenceSearchService $moduleReferenceSearchService
     *
     * @return void
     */
    public function prepare(Configuration $messagingConfiguration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService, InterfaceToCallRegistry $interfaceToCallRegistry): void;

    /**
     * @param $extensionObject
     *
     * @return bool
     */
    public function canHandle($extensionObject): bool;

    public function getModuleExtensions(ServiceConfiguration $serviceConfiguration, array $serviceExtensions /* , InterfaceToCallRegistry $interfaceToCallRegistry  */): array;

    public function getModulePackageName(): string;
}
