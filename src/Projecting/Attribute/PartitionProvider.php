<?php

/*
 * licence Enterprise
 */
declare(strict_types=1);

namespace Ecotone\Projecting\Attribute;

use Attribute;

/**
 * Marks a class as a custom PartitionProvider.
 * The class must implement \Ecotone\Projecting\PartitionProvider interface.
 * Userland partition providers are prioritized over built-in ones.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class PartitionProvider
{
}
