<?php

namespace Messaging\Message;
use Messaging\Exception\Message\InvalidMessageHeaderException;

/**
 * Class Uuid
 * @package Messaging\Message
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
final class Uuid
{
    private const UUID_REGEX = '/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i';

    /**
     * @var string
     */
    private $uuid;

    /**
     * Uuid constructor.
     * @param string $uuid
     */
    private function __construct(string $uuid)
    {
        $this->initialize($uuid);
    }

    /**
     * @param string $uuid
     * @return Uuid
     */
    public static function create(string $uuid) : self
    {
        return new self($uuid);
    }

    /**
     * @return string
     */
    public function toString() : string
    {
        return $this->uuid;
    }

    /**
     * @param string $uuid
     * @throws \Messaging\Exception\MessagingException
     */
    private function initialize(string $uuid) : void
    {
        if (!preg_match(self::UUID_REGEX, $uuid)) {
            throw InvalidMessageHeaderException::create("Invalid uuid {$uuid}");
        }

        $this->uuid = $uuid;
    }
}