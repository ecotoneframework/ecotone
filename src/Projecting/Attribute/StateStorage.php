<?php

/*
 * licence Enterprise
 */
declare(strict_types=1);

namespace Ecotone\Projecting\Attribute;

use Attribute;

/**
 * Marks a class as a custom ProjectionStateStorage.
 * The class must implement \Ecotone\Projecting\ProjectionStateStorage interface.
 * Userland state storages are prioritized over built-in ones.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class StateStorage
{
}
