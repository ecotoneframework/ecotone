<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Conversion;

use SimplyCodedSoftware\IntegrationMessaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\TypeDescriptor;
use SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException;

/**
 * Class ReferenceConverterBuilder
 * @package SimplyCodedSoftware\IntegrationMessaging\Conversion
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ReferenceServiceConverterBuilder implements ConverterBuilder
{
    /**
     * @var string
     */
    private $referenceName;
    /**
     * @var string
     */
    private $method;
    /**
     * @var TypeDescriptor
     */
    private $sourceType;
    /**
     * @var TypeDescriptor
     */
    private $targetType;

    /**
     * ReferenceConverter constructor.
     * @param string $referenceName
     * @param string $method
     * @param TypeDescriptor $sourceType
     * @param TypeDescriptor $targetType
     */
    private function __construct(string $referenceName, string $method, TypeDescriptor $sourceType, TypeDescriptor $targetType)
    {
        $this->referenceName = $referenceName;
        $this->method = $method;
        $this->sourceType = $sourceType;
        $this->targetType = $targetType;
    }

    /**
     * @param string $referenceName
     * @param string $method
     * @param TypeDescriptor $sourceType
     * @param TypeDescriptor $targetType
     * @return ReferenceServiceConverterBuilder
     */
    public static function create(string $referenceName, string $method, TypeDescriptor $sourceType, TypeDescriptor $targetType) : self
    {
        return new self($referenceName, $method, $sourceType, $targetType);
    }

    /**
     * @inheritDoc
     */
    public function build(ReferenceSearchService $referenceSearchService): Converter
    {
        $object = $referenceSearchService->get($this->referenceName);

        $interfaceToCall = InterfaceToCall::createFromUnknownType($object, $this->method);

        if ($interfaceToCall->hasMoreThanOneParameter()) {
            throw InvalidArgumentException::create("Converter should have only single parameter: {$interfaceToCall}");
        }

        return ReferenceServiceConverter::create(
            $object,
            $this->method,
            $this->sourceType,
            $this->targetType
        );
    }
}