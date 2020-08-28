<?php

namespace Ecotone\Messaging\Handler\ErrorHandler;

interface ErrorMessageGateway
{
    /**
     * @return ErrorMessageDetails[]
     */
    public function listOrderedExceptionDetails() : array;

    public function reply(string $messageId) : void;

    public function delete(string $messageId) : void;

    /**
     * @var array $headerMapping E.g. ["name" => "placeOrder"]
     */
    public function replyAllHavingHeaders(array $headerMapping) : void;

    /**
     * @var array $headerMapping E.g. ["name" => "placeOrder"]
     */
    public function deleteAllHavingHeaders(array $headerMapping) : void;
}