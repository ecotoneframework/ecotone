<?php

namespace Test\Ecotone\Messaging\Fixture\Service\ServiceInterface;

use stdClass;

/**
 * Interface ServiceInterface
 * @package Test\Ecotone\Messaging\Fixture\Service
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
interface ServiceInterfaceReceiveOnlyStdClass
{
    public function sendMail(): stdClass;
}
