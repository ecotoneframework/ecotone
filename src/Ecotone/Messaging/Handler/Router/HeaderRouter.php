<?php


namespace Ecotone\Messaging\Handler\Router;


use Ecotone\Messaging\Message;

class HeaderRouter
{
    private string $headerName;

    private function __construct(string $headerName)
    {
        $this->headerName = $headerName;
    }

    public static function create(string $headerName) : self
    {
        return new self($headerName);
    }

    public function route(Message $message) : string
    {
        return $message->getHeaders()->get($this->headerName);
    }
}