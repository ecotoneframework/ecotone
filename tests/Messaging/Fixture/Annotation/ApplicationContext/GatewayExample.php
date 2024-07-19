<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\ApplicationContext {
    use Ecotone\Messaging\Attribute\MessageGateway;

    /**
     * licence Apache-2.0
     */
    interface GatewayExample
    {
        #[MessageGateway(ApplicationContextExample::HTTP_INPUT_CHANNEL)]
        public function doSomething(): string;
    }
}
