<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Enricher;
use Ecotone\Messaging\Handler\ExpressionEvaluationService;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\InvalidArgumentException;

/**
 * Class PayloadPropertySetter
 * @package Ecotone\Messaging\Handler\Enricher\Converter
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class PropertyEditorAccessor
{
    private string $mappingExpression;
    private \Ecotone\Messaging\Handler\ExpressionEvaluationService $expressionEvaluationService;
    private \Ecotone\Messaging\Handler\ReferenceSearchService $referenceSearchService;

    /**
     * DataSetter constructor.
     * @param ExpressionEvaluationService $expressionEvaluationService
     * @param ReferenceSearchService $referenceSearchService
     * @param string $mappingExpression
     */
    private function __construct(ExpressionEvaluationService $expressionEvaluationService, ReferenceSearchService $referenceSearchService, string $mappingExpression)
    {
        $this->mappingExpression = $mappingExpression;
        $this->expressionEvaluationService = $expressionEvaluationService;
        $this->referenceSearchService = $referenceSearchService;
    }

    /**
     * @param ReferenceSearchService $referenceSearchService
     * @param string $mappingExpression
     * @return PropertyEditorAccessor
     * @throws \Ecotone\Messaging\Handler\ReferenceNotFoundException
     */
    public static function createWithMapping(ReferenceSearchService $referenceSearchService, string $mappingExpression): self
    {
        return new self(
            $referenceSearchService->get(ExpressionEvaluationService::REFERENCE),
            $referenceSearchService,
            $mappingExpression
        );
    }

    /**
     * @param ReferenceSearchService $referenceSearchService
     * @return PropertyEditorAccessor
     * @throws \Ecotone\Messaging\Handler\ReferenceNotFoundException
     */
    public static function create(ReferenceSearchService $referenceSearchService) : self
    {
        return self::createWithMapping($referenceSearchService, "");
    }

    /**
     * @param PropertyPath $propertyNamePath
     * @param mixed $dataToEnrich
     * @param mixed $dataToEnrichWith
     *
     * @param Message $requestMessage
     * @param null|Message $replyMessage
     * @return mixed enriched data
     * @throws EnrichException
     * @throws \ReflectionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function enrichDataWith(PropertyPath $propertyNamePath, $dataToEnrich, $dataToEnrichWith, Message $requestMessage, ?Message $replyMessage)
    {
        $propertyName = $propertyNamePath->getPath();

        if (preg_match("#^(\[\*\])#", $propertyName)) {
            $propertyToBeChanged = $this->cutOutCurrentAccessPropertyName($propertyNamePath, "[*]");
            $newPayload = $dataToEnrich;
            foreach ($dataToEnrich as $propertyKey => $context) {
                Assert::isIterable($dataToEnrichWith, "Data provided to enrich {$propertyNamePath->getPath()} is not iterable. Can't perform enriching.");

                $enriched = false;
                foreach ($dataToEnrichWith as $replyElement) {
                    if ($this->canBeMapped($context, $replyElement, $requestMessage, $replyMessage)) {
                        $newPayload[$propertyKey] = $this->enrichDataWith($propertyToBeChanged, $newPayload[$propertyKey], $replyElement, $requestMessage, $replyMessage);
                        $enriched = true;
                        break;
                    };
                }

                if (!$enriched) {
                    throw InvalidArgumentException::createWithFailedMessage("Can't enrich message {$requestMessage}. Can't find mapped data for {$propertyKey} in {$replyMessage}", $requestMessage);
                }
            }

            return $newPayload;
        }

        /** [0][data][worker] */
        preg_match("#^\[([a-zA-Z0-9]*)\]#", $propertyNamePath->getPath(), $startingWithPath);
        if ($this->hasAnyMatches($startingWithPath)) {
            $propertyName = $startingWithPath[1];
            $accessPropertyName = $startingWithPath[0];


            if ($accessPropertyName !== $propertyNamePath->getPath()) {
                $extractedPropertyName = $this->cutOutCurrentAccessPropertyName($propertyNamePath, $accessPropertyName);
                if ($extractedPropertyName->getPath() === "[]") {
                    $dataToEnrichWithAsArray = $dataToEnrich[$propertyName];
                    if (!is_array($dataToEnrichWithAsArray)) {
                        throw EnrichException::createWithFailedMessage("Can't enrich message {$requestMessage}. Enriched element {$accessPropertyName} should be array.", $requestMessage);
                    }

                    $dataToEnrichWithAsArray[] = $dataToEnrichWith;
                    $dataToEnrichWith = $dataToEnrichWithAsArray;
                }else {
                    $dataToEnrichWith = $this->enrichDataWith($extractedPropertyName, $dataToEnrich[$propertyName], $dataToEnrichWith, $requestMessage, $replyMessage);
                }
            }
        }else {
            /** worker[name] */
            preg_match('#\b([^\[\]]*)\[[a-zA-Z0-9]*\]#', $propertyNamePath->getPath(), $startingWithPropertyName);

            if ($this->hasAnyMatches($startingWithPropertyName)) {
                $propertyName = $startingWithPropertyName[1];

                if ($propertyName !== $propertyNamePath->getPath()) {
                    $dataToEnrichWith = $this->enrichDataWith($this->cutOutCurrentAccessPropertyName($propertyNamePath, $propertyName), $dataToEnrich[$propertyName], $dataToEnrichWith, $requestMessage, $replyMessage);
                }
            }
        }

        if (is_array($dataToEnrich)) {
            $newPayload = $dataToEnrich;
            $newPayload[$propertyName] = $dataToEnrichWith;

            return $newPayload;
        }

        if (is_object($dataToEnrich)) {
            $setterMethod = "set" . ucfirst($propertyName);

            if (method_exists($dataToEnrich, $setterMethod)) {
                $dataToEnrich->{$setterMethod}($dataToEnrichWith);

                return $dataToEnrich;
            }

            $objectReflection = new \ReflectionClass($dataToEnrich);

            if (!$objectReflection->hasProperty($propertyName)) {
                throw EnrichException::create("Object for enriching has no property named {$propertyName}");
            }

            $classProperty = $objectReflection->getProperty($propertyName);

            $classProperty->setAccessible(true);
            $classProperty->setValue($dataToEnrich, $dataToEnrichWith);

            return $dataToEnrich;
        }
    }

    /**
     * @param $matches
     *
     * @return bool
     */
    private function hasAnyMatches($matches): bool
    {
        return !empty($matches);
    }

    /**
     * @param PropertyPath $propertyName
     * @param string $accessPropertyName
     *
     * @return PropertyPath
     */
    private function cutOutCurrentAccessPropertyName(PropertyPath $propertyName, string $accessPropertyName) : PropertyPath
    {
        return PropertyPath::createWith(substr($propertyName->getPath(), strlen($accessPropertyName), strlen($propertyName->getPath())));
    }

    /**
     * @param $context
     * @param $replyElement
     * @param Message $requestMessage
     * @param null|Message $replyMessage
     * @return bool
     */
    private function canBeMapped($context, $replyElement, Message $requestMessage, ?Message $replyMessage): bool
    {
        return $this->expressionEvaluationService->evaluate(
            $this->mappingExpression,
            [
                "payload" => $replyMessage ? $replyMessage->getPayload() : null,
                "headers" => $replyMessage ? $replyMessage->getHeaders()->headers() : null,
                "request" => [
                    "payload" => $requestMessage->getPayload(),
                    "headers" => $requestMessage->getHeaders()
                ],
                "requestContext" => $context,
                "replyContext" => $replyElement
            ],
            $this->referenceSearchService
        );
    }
}