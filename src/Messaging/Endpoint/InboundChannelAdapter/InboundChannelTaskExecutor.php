<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Endpoint\InboundChannelAdapter;

use Ecotone\Messaging\Endpoint\EntrypointGateway;
use Ecotone\Messaging\Scheduling\TaskExecutor;

/**
 * Class InboundChannelGatewayExecutor
 * @package Ecotone\Messaging\Endpoint\InboundChannelAdapter
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
class InboundChannelTaskExecutor implements TaskExecutor
{
    /**
     * @var object
     */
    private $serviceToCall;
    /**
     * @var string
     */
    private $method;
    /**
     * @var EntrypointGateway
     */
    private $inboundChannelGateway;

    /**
     * InboundChannelGatewayExecutor constructor.
     * @param EntrypointGateway $inboundChannelGateway
     * @param $serviceToCall
     * @param string $method
     */
    public function __construct(EntrypointGateway $inboundChannelGateway, $serviceToCall, string $method)
    {
        $this->serviceToCall = $serviceToCall;
        $this->method = $method;
        $this->inboundChannelGateway = $inboundChannelGateway;
    }

    /**
     *
     */
    public function execute(): void
    {
        $result = call_user_func_array([$this->serviceToCall, $this->method], []);

        if (!is_null($result)) {
            $this->inboundChannelGateway->executeEntrypoint($result);
        }
    }
}