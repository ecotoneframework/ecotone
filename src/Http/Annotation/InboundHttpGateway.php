<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Http\Annotation;

use Doctrine\Common\Annotations\Annotation\Target;
use SimplyCodedSoftware\Messaging\Annotation\Gateway\Gateway;

/**
 * Class InboundHttpGateway
 * @package SimplyCodedSoftware\Http\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 * @Target({"METHOD"})
 */
class InboundHttpGateway extends Gateway
{

}