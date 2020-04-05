<?php


namespace Test\Ecotone\Messaging\Fixture\Handler\Gateway;


use Ecotone\Messaging\Message;

interface MessageReturningGateway
{
    public function execute(string $replyMediaType) : Message;

    public function executeWithMetadata(string $data, array $metadata) : Message;

    public function executeWithMetadataWithDefault(string $data, array $metadata = []) : Message;

    public function executeNoParameter() : Message;
}