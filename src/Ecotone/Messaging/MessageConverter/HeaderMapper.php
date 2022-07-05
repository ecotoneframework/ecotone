<?php
declare(strict_types=1);

namespace Ecotone\Messaging\MessageConverter;

/**
 * Interface HeaderMapper
 * @package Ecotone\Messaging\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface HeaderMapper
{
    /**
     * @param array $headersToBeMapped
     * @return array
     */
    public function mapToMessageHeaders(array $headersToBeMapped) : array;

    /**
     * @param array $headersToBeMapped
     * @return array
     */
    public function mapFromMessageHeaders(array $headersToBeMapped) : array;
}