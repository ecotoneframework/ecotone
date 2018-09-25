<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker;

use SimplyCodedSoftware\IntegrationMessaging\Handler\InterfaceParameter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ParameterConverter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\Message;
use SimplyCodedSoftware\IntegrationMessaging\Support\Assert;

/**
 * Class ServiceReferenceParameterConverter
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
class ReferenceConverter implements ParameterConverter
{
    /**
     * @var ReferenceSearchService
     */
    private $referenceSearchService;
    /**
     * @var string
     */
    private $parameterName;
    /**
     * @var object
     */
    private $referenceService;

    /**
     * ServiceReferenceParameterConverter constructor.
     * @param ReferenceSearchService $referenceSearchService
     * @param string $parameterName
     * @param object|null $serviceReference
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
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
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public static function create(ReferenceSearchService $referenceSearchService, string $parameterName, string $serviceReference) : self
    {
        return new self(
            $referenceSearchService,
            $parameterName,
            $serviceReference ? $referenceSearchService->findByReference($serviceReference) : null
        );
    }

    /**
     * @inheritDoc
     */
    public function getArgumentFrom(InterfaceParameter $relatedParameter, Message $message)
    {
        return $this->referenceService ? $this->referenceService : $this->referenceSearchService->findByReference($relatedParameter->getTypeHint());
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
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
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