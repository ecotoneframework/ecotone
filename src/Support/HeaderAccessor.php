<?php

namespace Messaging\Support;
use Messaging\MessageHeaderDoesNotExistsException;
use Messaging\MessageHeaders;
use Messaging\MessagingException;

/**
 * Class HeaderAccessor
 * @package Messaging\Support
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal class intent to be used with {@link Message}
 */
final class HeaderAccessor
{
    /**
     * @var array
     */
    private $headers;

    /**
     * HeaderAccessor constructor.
     * @param array $headers
     */
    private function __construct(array $headers)
    {
        $this->headers = $headers;
    }

    /**
     * @return array
     */
    public function headers() : array
    {
        return $this->headers;
    }

    /**
     * @return HeaderAccessor
     */
    public static function create() : self
    {
        return new self([]);
    }

    /**
     * @param string $headerName
     * @return bool
     */
    public function hasHeader(string $headerName) : bool
    {
        return array_key_exists($headerName, $this->headers);
    }

    /**
     * @param string $headerName
     * @return mixed
     * @throws MessagingException
     */
    public function getHeader(string $headerName)
    {
        if (!$this->hasHeader($headerName)) {
            throw MessageHeaderDoesNotExistsException::create("Header with name {$headerName} does not exists");
        }

        return $this->headers[$headerName];
    }

    /**
     * @param string $headerName
     * @param mixed $headerValue
     */
    public function setHeader(string $headerName, $headerValue) : void
    {
        $this->headers[$headerName] = $headerValue;
    }

    /**
     * @param string $headerName
     */
    public function removeHeader(string $headerName) : void
    {
        unset($this->headers[$headerName]);
    }

    /**
     * @param string $headerName
     * @param mixed $headerValue
     */
    public function setHeaderIfAbsent(string $headerName, $headerValue) : void
    {
        if ($this->hasHeader($headerName)) {
            return;
        }

        $this->headers[$headerName] = $headerValue;
    }

    /**
     * @param MessageHeaders $messageHeaders
     * @return HeaderAccessor
     */
    public static function createFrom(MessageHeaders $messageHeaders) : self
    {
        return new self($messageHeaders->headers());
    }
}