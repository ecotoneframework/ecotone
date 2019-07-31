<?php
declare(strict_types=1);

namespace Ecotone\Http\Annotation;

use Doctrine\Common\Annotations\Annotation\Target;
use Ecotone\Messaging\Annotation\Gateway;

/**
 * Class InboundHttpGateway
 * @package Ecotone\Http\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 * @Target({"METHOD"})
 */
class InboundHttpGateway extends Gateway
{

}