<?php
declare(strict_types=1);

namespace Ecotone\Tests\Messaging\Fixture\Annotation\ApplicationContext {

    use Ecotone\Messaging\Attribute\MessageGateway;

    interface GatewayExample
    {
        #[MessageGateway(ApplicationContextExample::HTTP_INPUT_CHANNEL)]
        public function doSomething(): string;
    }
}