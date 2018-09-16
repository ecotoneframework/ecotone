<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ModuleConfiguration;

use SimplyCodedSoftware\IntegrationMessaging\Annotation\Parameter\Expression;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Parameter\Header;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Parameter\MessageParameter;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Parameter\Payload;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Parameter\Reference;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Parameter\Value;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilderWithParameterConverters;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\ConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\ExpressionBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\HeaderBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\MessageConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\PayloadBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\ReferenceBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\ValueBuilder;

/**
 * Class ParameterConverterAnnotationFactory
 * @package SimplyCodedSoftware\IntegrationMessaging\Config\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ParameterConverterAnnotationFactory
{
    private function __construct()
    {
    }

    /**
     * @return ParameterConverterAnnotationFactory
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * @param InterfaceToCall|null $relatedClassInterface
     * @param array $parameterConverterAnnotations
     *
     * @return array
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException
     */
    public function createParameterConverters(?InterfaceToCall $relatedClassInterface, array $parameterConverterAnnotations): array
    {
        $parameterConverters = [];

        foreach ($parameterConverterAnnotations as $parameterConverterAnnotation) {
            if ($parameterConverterAnnotation instanceof Header) {
                if ($parameterConverterAnnotation->isRequired) {
                    $parameterConverters[] = HeaderBuilder::create($parameterConverterAnnotation->parameterName, $parameterConverterAnnotation->headerName);
                } else {
                    $parameterConverters[] = HeaderBuilder::createOptional($parameterConverterAnnotation->parameterName, $parameterConverterAnnotation->headerName);
                }
            } else if ($parameterConverterAnnotation instanceof Payload) {
                $parameterConverters[] = PayloadBuilder::create($parameterConverterAnnotation->parameterName);
            } else if ($parameterConverterAnnotation instanceof MessageParameter) {
                $parameterConverters[] = MessageConverterBuilder::create($parameterConverterAnnotation->parameterName);
            } else if ($parameterConverterAnnotation instanceof Reference) {
                if ($parameterConverterAnnotation->referenceName) {
                    $parameterConverters[] = ReferenceBuilder::create($parameterConverterAnnotation->parameterName, $parameterConverterAnnotation->referenceName);
                }elseif ($relatedClassInterface) {
                    $parameterConverters[] = ReferenceBuilder::createFromParameterTypeHint($parameterConverterAnnotation->parameterName, $relatedClassInterface);
                }else {
                    $parameterConverters[] = ReferenceBuilder::createWithDynamicResolve($parameterConverterAnnotation->parameterName);
                }
            } else if ($parameterConverterAnnotation instanceof Expression) {
                $parameterConverters[] = ExpressionBuilder::create(
                    $parameterConverterAnnotation->parameterName, $parameterConverterAnnotation->expression
                );
            } else if ($parameterConverterAnnotation instanceof Value) {
                $parameterConverters[] = ValueBuilder::create($parameterConverterAnnotation->parameterName, $parameterConverterAnnotation->value);
            }
        }

        return $parameterConverters;
    }
}