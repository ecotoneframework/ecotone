<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter;

use Ecotone\Messaging\Handler\InterfaceParameter;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\ParameterConverter;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\Support\Assert;

/**
 * Class ServiceReferenceParameterConverter
 * @package Ecotone\Messaging\Handler\Processor\MethodInvoker
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
class ReferenceConverter implements ParameterConverter
{
    private \Ecotone\Messaging\Handler\ReferenceSearchService $referenceSearchService;
    private string $parameterName;
    private ?object $referenceService = null;

    /**
     * ServiceReferenceParameterConverter constructor.
     * @param ReferenceSearchService $referenceSearchService
     * @param string $parameterName
     * @param object|null $serviceReference
     * @throws \Ecotone\Messaging\MessagingException
     */
    private function __construct(ReferenceSearchService $referenceSearchService, string $parameterName, $serviceReference)
    {
        $this->referenceSearchService = $referenceSearchService;
        $this->parameterName = $parameterName;

        $this->initialize($serviceReference);
    }

    /**
     * @param ReferenceSearchService $referenceSearchService
     * @param string $parameterName
     * @param string $serviceReference
     * @return ReferenceConverter
     * @throws \Ecotone\Messaging\MessagingException
     */
    public static function create(ReferenceSearchService $referenceSearchService, string $parameterName, string $serviceReference) : self
    {
        return new self(
            $referenceSearchService,
            $parameterName,
            $serviceReference ? $referenceSearchService->get($serviceReference) : null
        );
    }

    /**
     * @inheritDoc
     */
    public function getArgumentFrom(InterfaceToCall $interfaceToCall, InterfaceParameter $relatedParameter, Message $message, array $endpointAnnotations): object
    {
        return $this->referenceService ? $this->referenceService : $this->referenceSearchService->get($relatedParameter->getTypeHint());
    }

    /**
     * @inheritDoc
     */
    public function isHandling(InterfaceParameter $parameter): bool
    {
        return $parameter->getName() == $this->parameterName;
    }

    /**
     * @param object|null $serviceReference
     * @throws \Ecotone\Messaging\MessagingException
     */
    private function initialize($serviceReference) : void
    {
        if (!$serviceReference) {
            return;
        }

        Assert::isObject($serviceReference, "Reference must be object for " . self::class);

        $this->referenceService = $serviceReference;
    }
}