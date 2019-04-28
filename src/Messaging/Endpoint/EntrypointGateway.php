<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Endpoint;

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
     */
    public function execute($data);
}