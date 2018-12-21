<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Conversion\MessageConverter;

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
    public function map(array $headersToBeMapped) : array;
}