<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Endpoint\InboundChannelAdapter;

use SimplyCodedSoftware\Messaging\Scheduling\TaskExecutor;

/**
 * Class InboundChannelGatewayExecutor
 * @package SimplyCodedSoftware\Messaging\Endpoint\InboundChannelAdapter
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
     * @var InboundChannelGateway
     */
    private $inboundChannelGateway;

    /**
     * InboundChannelGatewayExecutor constructor.
     * @param InboundChannelGateway $inboundChannelGateway
     * @param $serviceToCall
     * @param string $method
     */
    public function __construct(InboundChannelGateway $inboundChannelGateway, $serviceToCall, string $method)
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

        if ($result) {
            $this->inboundChannelGateway->execute($result);
        }
    }
}