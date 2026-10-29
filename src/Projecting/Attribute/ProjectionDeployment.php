<?php

/*
 * licence Enterprise
 */
declare(strict_types=1);

namespace Ecotone\Projecting\Attribute;

use Attribute;

/**
 * Configure projection deployment settings for blue/green deployment scenarios.
 */
#[Attribute(Attribute::TARGET_CLASS)]
class ProjectionDeployment
{
    public function __construct(
        /**
         * When true, projection will not be automatically initialized.
         * It will require manual trigger
         *
         * Default: false (automatic initialization)
         */
        public readonly bool $manualKickOff = false,
        /**
         * When false, emitted events via EventStreamEmitter will not be published.
         * Use this for blue/green deployment to rebuild projection without
         * re-emitting events to downstream consumers.
         *
         * Default: true (events are emitted)
         */
        public readonly bool $live = true,
    ) {
    }
}
