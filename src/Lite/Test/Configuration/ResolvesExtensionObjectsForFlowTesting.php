<?php

declare(strict_types=1);

namespace Ecotone\Lite\Test\Configuration;

/**
 * licence Apache-2.0
 */
interface ResolvesExtensionObjectsForFlowTesting
{
    /**
     * @param object[] $extensionObjects
     * @return object[]
     */
    public function resolveForFlowTesting(array $extensionObjects, ProvidedReferences $providedReferences): array;
}
