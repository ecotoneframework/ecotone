<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Endpoint;

use SimplyCodedSoftware\Messaging\Handler\Logger\Annotation\LogBefore;
use SimplyCodedSoftware\Messaging\Handler\Logger\Annotation\LogError;

/**
 * Interface PollingConsumerGatewayEntrypoint
 * @package SimplyCodedSoftware\Messaging\Endpoint\PollingConsumer
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface EntrypointGateway
{
    /**
     * @param mixed $data
     * @return mixed
     * @LogBefore()
     */
    public function executeEntrypoint($data);
}