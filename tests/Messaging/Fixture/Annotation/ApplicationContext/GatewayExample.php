<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\ApplicationContext {

    use Ecotone\Messaging\Annotation\MessageGateway;
    use Ecotone\Messaging\Annotation\MessageEndpoint;

    /**
     * Interface Gateway
     * @package Test\Ecotone\Messaging\Fixture\Annotation\ApplicationContext
     * @author Dariusz Gafka <dgafka.mail@gmail.com>
     * @MessageEndpoint()
     */
    interface GatewayExample
    {
        /**
         * @return string
         * @MessageGateway()
         */
        public function doSomething(): string;
    }
}