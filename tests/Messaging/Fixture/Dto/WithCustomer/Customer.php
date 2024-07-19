<?php

namespace Test\Ecotone\Messaging\Fixture\Dto\WithCustomer;

/**
 * Class Customer
 * @package Test\Ecotone\Messaging\Fixture\Dto\WithCustomer
 * @author  Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class Customer
{
    /**
     * @var string
     */
    private $username;
    /**
     * @var string
     */
    public $phoneNumber;
    /**
     * @var string
     */
    private $password;

    /**
     * Customer constructor.
     *
     * @param string $username
     * @param string $phoneNumber
     * @param string $password
     */
    public function __construct(string $username, string $phoneNumber, string $password)
    {
        $this->username    = $username;
        $this->phoneNumber = $phoneNumber;
        $this->password    = $password;
    }


    /**
     * @param string $username
     * @param string $phoneNumber
     * @param string $password
     *
     * @return Customer
     */
    public static function create(string $username, string $phoneNumber, string $password): self
    {
        return new self($username, $phoneNumber, $password);
    }

    /**
     * @param string $username
     *
     * @return Customer
     */
    public static function createWithUsernameOnly(string $username): self
    {
        return new self($username, '', '');
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @param string $username
     */
    public function setUsername(string $username)
    {
        $this->username = $username;
    }
}
