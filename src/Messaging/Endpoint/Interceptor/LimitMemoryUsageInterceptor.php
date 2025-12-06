<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Endpoint\Interceptor;

use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Endpoint\ConsumerInterceptor;
use Ecotone\Messaging\Endpoint\ConsumerInterceptorTrait;

/**
 * Class LimitMemoryUsageInterceptor
 * @package Ecotone\Messaging\Endpoint\Interceptor
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class LimitMemoryUsageInterceptor implements ConsumerInterceptor
{
    use ConsumerInterceptorTrait;
    private int $memoryLimitInMegaBytes;

    /**
     * LimitMemoryUsageInterceptor constructor.
     * @param int $memoryLimitInMegaBytes
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function __construct(int $memoryLimitInMegaBytes)
    {
        if ($memoryLimitInMegaBytes < 0) {
            throw ConfigurationException::create("Memory limit usage is set to incorrect value: {$memoryLimitInMegaBytes}");
        }

        $this->memoryLimitInMegaBytes = $memoryLimitInMegaBytes * 1024 * 1024;
    }

    /**
     * @inheritDoc
     */
    public function shouldBeStopped(): bool
    {
        if ($this->memoryLimitInMegaBytes === 0) {
            return false;
        }

        return memory_get_usage(true) >= $this->memoryLimitInMegaBytes;
    }
}
