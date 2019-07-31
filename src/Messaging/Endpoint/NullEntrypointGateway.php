<?php


namespace Ecotone\Messaging\Endpoint;

/**
 * Class NullEntrypointGateway
 * @package Ecotone\Messaging\Endpoint
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class NullEntrypointGateway implements EntrypointGateway
{
    private function __construct()
    {
    }

    public static function create() : self
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public function executeEntrypoint($data)
    {
        return;
    }
}