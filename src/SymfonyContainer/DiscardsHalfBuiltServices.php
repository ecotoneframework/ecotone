<?php

declare(strict_types=1);

namespace Ecotone\SymfonyContainer;

use Symfony\Component\DependencyInjection\ContainerInterface as SymfonyContainerInterface;
use Throwable;

/**
 * Shared services are memoized the moment they are constructed — before their
 * collaborators are wired (Symfony's circular-reference pattern). When wiring
 * throws (e.g. an external reference is missing), those half-built services
 * would stay memoized and every retry would fail with a misleading follow-up
 * error ("no message handler registered") instead of the original one. This
 * discards everything memoized during a failed resolution, so retrying fails
 * with the same honest error.
 *
 * licence Apache-2.0
 */
trait DiscardsHalfBuiltServices
{
    public function get(string $id, int $invalidBehavior = SymfonyContainerInterface::EXCEPTION_ON_INVALID_REFERENCE): ?object
    {
        if (isset($this->services[$id])) {
            return $this->services[$id];
        }

        $servicesBefore = array_keys($this->services);
        $privatesBefore = array_keys($this->privates);

        try {
            return parent::get($id, $invalidBehavior);
        } catch (Throwable $failure) {
            foreach (array_diff(array_keys($this->services), $servicesBefore) as $halfBuilt) {
                unset($this->services[$halfBuilt]);
            }
            foreach (array_diff(array_keys($this->privates), $privatesBefore) as $halfBuilt) {
                unset($this->privates[$halfBuilt]);
            }

            throw $failure;
        }
    }
}
