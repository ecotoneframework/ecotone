<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Endpoint;

use Ecotone\Messaging\Handler\Logger\Annotation\LogBefore;
use Ecotone\Messaging\Handler\Logger\Annotation\LogError;

/**
 * Interface PollingConsumerGatewayEntrypoint
 * @package Ecotone\Messaging\Endpoint\PollingConsumer
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface InboundGatewayEntrypoint
{
    #[LogBefore]
    #[LogError]
    public function executeEntrypoint($data);
}