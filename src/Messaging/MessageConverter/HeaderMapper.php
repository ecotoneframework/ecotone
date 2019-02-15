<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\MessageConverter;

/**
 * Interface HeaderMapper
 * @package SimplyCodedSoftware\Messaging\Handler
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