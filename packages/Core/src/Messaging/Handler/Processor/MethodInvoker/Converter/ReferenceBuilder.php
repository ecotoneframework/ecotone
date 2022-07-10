<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter;

use Ecotone\Messaging\Handler\InterfaceParameter;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\ParameterConverter;
use Ecotone\Messaging\Handler\ParameterConverterBuilder;
use Ecotone\Messaging\Handler\ReferenceSearchService;

/**
 * Class ReferenceBuilder
 * @package Ecotone\Messaging\Handler\Processor\MethodInvoker
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ReferenceBuilder implements ParameterConverterBuilder
{
    private string $parameterName;
    private string $referenceServiceName;

    /**
     * ServiceReferenceParameterConverter constructor.
     * @param string $parameterName
     * @param string $referenceName
     */
    private function __construct(string $parameterName, string $referenceName)
    {
        $this->parameterName = $parameterName;
        $this->referenceServiceName = $referenceName;
    }

    /**
     * @param string $parameterName
     * @param string $referenceServiceName
     * @return ReferenceBuilder
     */
    public static function create(string $parameterName, string $referenceServiceName) : self
    {
        return new self($parameterName, $referenceServiceName);
    }

    /**
     * @inheritDoc
     */
    public function isHandling(InterfaceParameter $parameter): bool
    {
        return $parameter->getName() === $this->parameterName;
    }

    /**
     * @param string $parameterName
     * @return ReferenceBuilder
     */
    public static function createWithDynamicResolve(string $parameterName) : self
    {
        return new self($parameterName, "");
    }

    /**
     * @param string $parameterName
     * @param InterfaceToCall $referenceClass
     * @return ReferenceBuilder
     * @throws \Ecotone\Messaging\MessagingException
     * @throws \Ecotone\Messaging\Support\InvalidArgumentException
     */
    public static function createFromParameterTypeHint(string $parameterName, InterfaceToCall $referenceClass) : self
    {
        return new self($parameterName, $referenceClass->getParameterWithName($parameterName)->getTypeHint());
    }

    /**
     * @inheritDoc
     */
    public function build(ReferenceSearchService $referenceSearchService): ParameterConverter
    {
        return ReferenceConverter::create(
            $referenceSearchService,
            $this->parameterName,
            $this->referenceServiceName
        );
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferences(): array
    {
        return [$this->referenceServiceName];
    }
}