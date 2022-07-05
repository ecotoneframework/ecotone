<?php
declare(strict_types=1);


namespace Ecotone\Messaging\Handler\Logger\Annotation;

use Ecotone\Messaging\Handler\Logger\Logger;

#[\Attribute(\Attribute::TARGET_METHOD)]
class LogBefore extends Logger
{

}