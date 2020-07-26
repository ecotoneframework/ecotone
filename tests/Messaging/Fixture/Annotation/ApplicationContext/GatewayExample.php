<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\ApplicationContext {

    use Ecotone\Messaging\Annotation\MessageGateway;
    use Ecotone\Messaging\Annotation\MessageEndpoint;

    interface GatewayExample
    {
        /**
         * @return string
         * @MessageGateway()
         */
        public function doSomething(): string;
    }
}