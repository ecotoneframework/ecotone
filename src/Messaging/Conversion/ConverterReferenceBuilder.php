<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Conversion;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;

/**
 * Class ConverterReferenceBuilder
 * @package SimplyCodedSoftware\Messaging\Conversion
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ConverterReferenceBuilder implements ConverterBuilder
{
    /**
     * @var string
     */
    private $referenceName;

    /**
     * ConverterReferenceBuilder constructor.
     * @param string $referenceName
     */
    private function __construct(string $referenceName)
    {
        $this->referenceName = $referenceName;
    }

    /**
     * @param string $referenceName
     * @return ConverterReferenceBuilder
     */
    public static function create(string $referenceName) : self
    {
        return new self($referenceName);
    }

    /**
     * @inheritDoc
     */
    public function build(ReferenceSearchService $referenceSearchService): Converter
    {
        return $referenceSearchService->get($this->referenceName);
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferences(): array
    {
        return [$this->referenceName];
    }
}