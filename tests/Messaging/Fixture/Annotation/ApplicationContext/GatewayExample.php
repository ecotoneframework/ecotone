<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\ApplicationContext {

    use SimplyCodedSoftware\Messaging\Annotation\Gateway\Gateway;
    use SimplyCodedSoftware\Messaging\Annotation\MessageEndpoint;

    /**
     * Interface Gateway
     * @package Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\ApplicationContext
     * @author Dariusz Gafka <dgafka.mail@gmail.com>
     * @MessageEndpoint()
     */
    interface GatewayExample
    {
        /**
         * @return string
         * @Gateway()
         */
        public function doSomething(): string;
    }
}