<?php

namespace SimplyCodedSoftware\Messaging\Rabbitmq;

use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Connection\AMQPStreamConnection;

/**
 * Interface ConnectionFactory
 * @package SimplyCodedSoftware\Messaging\Rabbitmq
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class RabbitmqConnectionFactory implements ConnectionFactory
{
    private const DEFAULT_USER = "guest";
    private const DEFAULT_PASS = "guest";
    private const DEFAULT_VHOST = "/";
    private const DEFAULT_HOST = "localhost";
    private const DEFAULT_AMQP_PORT = 5672;
    private const DEFAULT_HEARTBEAT = 0;
    private const DEFAULT_AMQP_OVER_SSL_PORT = 5671;
    private const DEFAULT_CONNECTION_TIMEOUT = 6.0;
    private const DEFAULT_READ_WRITE_TIMEOUT = 4.0;
    private const DEFAULT_LOGIN_METHOD = 'AMQPLAIN';
    private const DEFAULT_LOCALE = 'en_US';

    /**
     * @var string
     */
    private $host = self::DEFAULT_HOST;
    /**
     * @var string
     */
    private $username = self::DEFAULT_USER;
    /**
     * @var string
     */
    private $password = self::DEFAULT_PASS;
    /**
     * @var string
     */
    private $port = self::DEFAULT_AMQP_PORT;
    /**
     * @var string
     */
    private $virtualHost = self::DEFAULT_VHOST;
    /**
     * @var int
     */
    private $requestedHeartbeat = self::DEFAULT_HEARTBEAT;
    /**
     * @var float
     */
    private $connectionTimeout = self::DEFAULT_CONNECTION_TIMEOUT;
    /**
     * @var float
     */
    private $readAndWriteTimeout = self::DEFAULT_READ_WRITE_TIMEOUT;

    private function __construct()
    {
    }

    /**
     * @return RabbitmqConnectionFactory
     */
    public static function create() : self
    {
        return new self();
    }

    /**
     * @param string $host
     * @return RabbitmqConnectionFactory
     */
    public function setHost(string $host) : RabbitmqConnectionFactory
    {
        $this->host = $host;

        return $this;
    }

    /**
     * @param string $username
     * @return RabbitmqConnectionFactory
     */
    public function setUsername(string $username) : RabbitmqConnectionFactory
    {
        $this->username = $username;

        return $this;
    }

    /**
     * @param string $password
     * @return RabbitmqConnectionFactory
     */
    public function setPassword(string $password) : RabbitmqConnectionFactory
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @param string $port
     * @return RabbitmqConnectionFactory
     */
    public function setPort(string $port) : RabbitmqConnectionFactory
    {
        $this->port = $port;

        return $this;
    }

    /**
     * @param string $virtualHost
     * @return RabbitmqConnectionFactory
     */
    public function setVirtualHost(string $virtualHost) : RabbitmqConnectionFactory
    {
        $this->virtualHost = $virtualHost;

        return $this;
    }

    /**
     * @param int $requestedHeartbeat
     */
    public function setRequestedHeartbeat(int $requestedHeartbeat)
    {
        $this->requestedHeartbeat = $requestedHeartbeat;
    }

    /**
     * @param float $connectionTimeout
     */
    public function setConnectionTimeout(float $connectionTimeout)
    {
        $this->connectionTimeout = $connectionTimeout;
    }

    /**
     * @param float $readAndWriteTimeout
     */
    public function setReadAndWriteTimeout(float $readAndWriteTimeout)
    {
        $this->readAndWriteTimeout = $readAndWriteTimeout;
    }

    /**
     * @return AbstractConnection
     */
    public function createConnection() : AbstractConnection
    {
        return new AMQPStreamConnection(
            $this->host,
            $this->port,
            $this->username,
            $this->password,
            $this->virtualHost,
            false,
            self::DEFAULT_LOGIN_METHOD,
            null,
            self::DEFAULT_LOCALE,
            $this->connectionTimeout,
            $this->readAndWriteTimeout,
            null,
            false,
            $this->requestedHeartbeat
        );
    }
}