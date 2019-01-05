<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Conversion\MessageConverter;
use SimplyCodedSoftware\Messaging\Support\Assert;

/**
 * Class CombinedHeaderMapper
 * @package SimplyCodedSoftware\Messaging\Conversion\MessageConverter
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class CombinedHeaderMapper implements HeaderMapper
{
    /**
     * @var HeaderMapper[]
     */
    private $headerMappers = [];

    /**
     * CombinedHeaderMapper constructor.
     * @param HeaderMapper[] $headerMappers
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    private function __construct(array $headerMappers)
    {
        Assert::allInstanceOfType($headerMappers, HeaderMapper::class);
        $this->headerMappers = $headerMappers;
    }

    /**
     * @param HeaderMapper[] $headerMappers
     * @return CombinedHeaderMapper
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public static function createWith($headerMappers) : self
    {
        return new self($headerMappers);
    }

    /**
     * @inheritDoc
     */
    public function map(array $headersToBeMapped): array
    {
        $mappedHeaders = $headersToBeMapped;

        foreach ($this->headerMappers as $headerMapper) {
            $mappedHeaders = $headerMapper->map($mappedHeaders);
        }

        return $mappedHeaders;
    }
}