<?php

namespace Messaging\Registry;

use Messaging\Message\Uuid;
use Messaging\UuidGenerator;

/**
 * Class DumbUuidGenerator
 * @package Messaging\Registry
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class DumbUuidGenerator implements UuidGenerator
{
    /**
     * @var string
     */
    private $uuid;

    /**
     * DumbClock constructor.
     * @param string $uuid
     */
    private function __construct(string $uuid)
    {
        $this->uuid = $uuid;
    }

    /**
     * @param string $uuid
     * @return self
     */
    public static function create(string $uuid) : self
    {
        return new self($uuid);
    }

    /**
     * @inheritDoc
     */
    public function generateUuid(): Uuid
    {
        return Uuid::create($this->uuid);
    }
}