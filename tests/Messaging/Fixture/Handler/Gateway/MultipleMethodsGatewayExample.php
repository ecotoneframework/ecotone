<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Handler\Gateway;

use Ecotone\Messaging\Annotation\Gateway;
use Ecotone\Messaging\Annotation\MessageEndpoint;

/**
 * Interface MultipleMethodsGatewayExample
 * @package Test\Ecotone\Messaging\Fixture\Handler\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageEndpoint()
 */
interface MultipleMethodsGatewayExample
{
    /**
     * @Gateway(requestChannel="channel1")
     * @param $data
     */
    public function execute1($data) : void;

    /**
     * @Gateway(requestChannel="channel2")
     * @param $data
     */
    public function execute2($data) : void;
}