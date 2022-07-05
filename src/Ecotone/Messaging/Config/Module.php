<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Config;

use Ecotone\Messaging\Handler\InterfaceToCallRegistry;

/**
 * Interface ExternalConfiguration
 * @package Ecotone\Messaging\Config
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
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
    public function prepare(Configuration $configuration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService, InterfaceToCallRegistry $interfaceToCallRegistry): void;

    /**
     * @param $extensionObject
     *
     * @return bool
     */
    public function canHandle($extensionObject): bool;

    /**
     * Which will be available during build configure phase
     *
     * @return RequiredReference[]|OptionalReference[]|string[]
     */
    public function getRelatedReferences(): array;

    public function getModuleExtensions(array $serviceExtensions) : array;
}