<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker;

use SimplyCodedSoftware\IntegrationMessaging\Handler\ParameterConverter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ParameterConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;

/**
 * Class ReferenceBuilder
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker
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
     * @inheritDoc
     */
    public function build(ReferenceSearchService $referenceSearchService): ParameterConverter
    {
        return ReferenceConverter::create(
            $this->parameterName,
            $referenceSearchService->findByReference($this->referenceServiceName)
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