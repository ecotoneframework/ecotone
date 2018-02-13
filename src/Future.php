<?php

namespace SimplyCodedSoftware\IntegrationMessaging;

/**
 * Class Future
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface Future
{
    /**
     * Resolves future by retrieving response
     *
     * @return mixed
     */
    public function resolve();
}