<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Logger\Annotation;

use Attribute;
use Ecotone\Messaging\Handler\Logger\Logger;

#[Attribute(Attribute::TARGET_METHOD)]
/**
 * licence Apache-2.0
 */
class LogBefore extends Logger
{
}
