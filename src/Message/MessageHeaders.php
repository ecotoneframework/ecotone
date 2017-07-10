<?php

namespace Messaging\Message;

use Messaging\Exception\Message\InvalidMessageHeaderException;

/**
 * Class MessageHeaders
 * @package Messaging\Message
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
final class MessageHeaders
{
    /**
     * @var array
     */
    private $headers;

    /**
     * MessageHeaders constructor.
     * @param array $headers
     */
    private function __construct(array $headers)
    {
        $this->initialize($headers);
    }

    /**
     * @return MessageHeaders
     */
    public static function createEmpty() : self
    {
        return new self([]);
    }

    /**
     * @param array|string[] $headers
     * @return MessageHeaders
     */
    public static function createWith(array $headers) : self
    {
        return new self($headers);
    }

    /**
     * @return array|string[]
     */
    public function headers() : array
    {
        return $this->headers;
    }

    /**
     * @param array|string[] $headers
     * @throws \Messaging\Exception\MessagingException
     */
    private function initialize(array $headers) : void
    {
        foreach ($headers as $headerName => $headerValue) {
            if (!$headerName) {
                throw InvalidMessageHeaderException::create("Passed empty header name");
            }
            if (!is_scalar($headerValue)) {
                throw InvalidMessageHeaderException::create("Passed header value {$headerName} is not correct type. It should be scalar");
            }
        }

        $this->headers = $headers;
    }
}