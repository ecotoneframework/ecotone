<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker;

use SimplyCodedSoftware\Messaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\Messaging\Handler\ParameterConverter;
use SimplyCodedSoftware\Messaging\Handler\ParameterConverterBuilder;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;

/**
 * Class ReferenceBuilder
 * @package SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ReferenceBuilder implements ParameterConverterBuilder
{
    /**
     * @var string
     */
    private $parameterName;
    /**
     * @var object
     */
    private $referenceServiceName;

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
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     * @throws \SimplyCodedSoftware\Messaging\Support\InvalidArgumentException
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