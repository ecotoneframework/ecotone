<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Http;

/**
 * Class HttpConfiguration
 * @package SimplyCodedSoftware\Http
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class HttpConfiguration
{
    /**
     * @var ContentTypeMapping[]
     */
    private $contentTypeMappings;

    private $uploadFileMover;
}