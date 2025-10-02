<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration;

use Ecotone\AnnotationFinder\AnnotatedMethod;
use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Messaging\Attribute\ConsoleCommand;
use Ecotone\Messaging\Attribute\ConsoleParameterOption;
use Ecotone\Messaging\Attribute\ModuleAnnotation;
use Ecotone\Messaging\Config\Annotation\AnnotatedDefinitionReference;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ConsoleCommandConfiguration;
use Ecotone\Messaging\Config\ConsoleCommandParameter;
use Ecotone\Messaging\Config\ConsoleCommandResultSet;
use Ecotone\Messaging\Config\Container\AttributeDefinition;
use Ecotone\Messaging\Config\Container\InterfaceToCallReference;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Config\ModulePackageList;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\HeaderBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\ReferenceBuilder;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\InvalidArgumentException;

#[ModuleAnnotation]
/**
 * licence Apache-2.0
 */
final class ConsoleCommandModule extends NoExternalConfigurationModule implements AnnotationModule
{
    public const ECOTONE_COMMAND_PARAMETER_PREFIX = 'ecotone.oneTimeCommand.';

    /**
     * @var ServiceActivatorBuilder[]
     */
    private array $oneTimeCommandHandlers;
    /**
     * @var ConsoleCommandConfiguration[]
     */
    private array $oneTimeCommandConfigurations;

    private function __construct(array $oneTimeCommands, array $oneTimeCommandConfigurations)
    {
        $this->oneTimeCommandHandlers = $oneTimeCommands;
        $this->oneTimeCommandConfigurations = $oneTimeCommandConfigurations;
    }

    public static function create(AnnotationFinder $annotationRegistrationService, InterfaceToCallRegistry $interfaceToCallRegistry): static
    {
        $messageHandlerBuilders    = [];
        $oneTimeConfigurations = [];

        foreach ($annotationRegistrationService->findAnnotatedMethods(ConsoleCommand::class) as $annotationRegistration) {
            /** @var ConsoleCommand $annotation */
            $annotation               = $annotationRegistration->getAnnotationForMethod();
            $commandName                     = $annotation->getName();
            $className    = $annotationRegistration->getClassName();
            $methodName               = $annotationRegistration->getMethodName();

            [$messageHandlerBuilder, $oneTimeCommandConfiguration] = self::prepareConsoleCommand($interfaceToCallRegistry, $annotationRegistration, $className, $methodName, $commandName);

            $messageHandlerBuilders[] = $messageHandlerBuilder;
            $oneTimeConfigurations[]     = $oneTimeCommandConfiguration;
        }

        return new static($messageHandlerBuilders, $oneTimeConfigurations);
    }

    public static function prepareConsoleCommand(InterfaceToCallRegistry $interfaceToCallRegistry, AnnotatedMethod $annotatedMethod, string $className, string $methodName, string $commandName): array
    {
        $parameterConverters = [];
        $parameters          = [];

        [$parameterConverters, $parameters] = self::prepareParameter($interfaceToCallRegistry, $className, $methodName, $parameterConverters, $parameters);

        $inputChannel                = 'ecotone.channel.' . $commandName;

        $messageHandlerBuilder       = ServiceActivatorBuilder::create(AnnotatedDefinitionReference::getReferenceFor($annotatedMethod), $interfaceToCallRegistry->getFor($annotatedMethod->getClassName(), $methodName))
            ->withEndpointId('ecotone.endpoint.' . $commandName)
            ->withInputChannelName($inputChannel)
            ->withMethodParameterConverters($parameterConverters);
        $oneTimeCommandConfiguration = ConsoleCommandConfiguration::create($inputChannel, $commandName, $parameters);

        return [$messageHandlerBuilder, $oneTimeCommandConfiguration];
    }

    public static function prepareConsoleCommandForReference(Reference $reference, InterfaceToCallReference $interfaceToCallReference, string $commandName, bool $discoverableByConsoleCommandAttribute, InterfaceToCallRegistry $interfaceToCallRegistry)
    {
        $className = $interfaceToCallReference->getClassName();
        $methodName = $interfaceToCallReference->getMethodName();
        $parameterConverters = [];
        $parameters          = [];

        [$parameterConverters, $parameters] = self::prepareParameter($interfaceToCallRegistry, $className, $methodName, $parameterConverters, $parameters);

        $inputChannel                = 'ecotone.channel.' . $commandName;
        $messageHandlerBuilder       = ServiceActivatorBuilder::create($reference->getId(), $interfaceToCallReference)
            ->withEndpointId('ecotone.endpoint.' . $commandName)
            ->withEndpointAnnotations($discoverableByConsoleCommandAttribute ? [new AttributeDefinition(ConsoleCommand::class, [$commandName])] : [])
            ->withInputChannelName($inputChannel)
            ->withMethodParameterConverters($parameterConverters);
        $oneTimeCommandConfiguration = ConsoleCommandConfiguration::create($inputChannel, $commandName, $parameters);

        return [$messageHandlerBuilder, $oneTimeCommandConfiguration];
    }

    private static function prepareParameter(InterfaceToCallRegistry $interfaceToCallRegistry, bool|string $className, string $methodName, array $parameterConverters, array $parameters): array
    {
        $parameterConverterAnnotationFactory = ParameterConverterAnnotationFactory::create();
        $interfaceToCall = $interfaceToCallRegistry->getFor($className, $methodName);

        if ($interfaceToCall->canReturnValue() && ! $interfaceToCall->getReturnType()->equals(Type::object(ConsoleCommandResultSet::class))) {
            throw InvalidArgumentException::create("One Time Command {$interfaceToCall} must have void or " . ConsoleCommandResultSet::class . ' return type');
        }

        foreach ($interfaceToCall->getInterfaceParameters() as $interfaceParameter) {
            Assert::isFalse($interfaceParameter->getName() === ConsoleCommandConfiguration::HEADER_PARAMETER_NAME, "Parameter name 'header' is reserved for headers and cannot be used as a parameter name");
            if ($parameterConverter = $parameterConverterAnnotationFactory->getConverterFor($interfaceParameter)) {
                $parameterConverters[] = $parameterConverter;
            } elseif ($interfaceParameter->getTypeDescriptor()->isClassOrInterface()) {
                $parameterConverters[] = ReferenceBuilder::create($interfaceParameter->getName(), $interfaceParameter->getTypeDescriptor()->toString());
            } else {
                $headerName = self::ECOTONE_COMMAND_PARAMETER_PREFIX . $interfaceParameter->getName();
                $parameterConverters[] = HeaderBuilder::create($interfaceParameter->getName(), $headerName);
                $parameterType = $interfaceParameter->getTypeDescriptor();
                $isOption = $interfaceParameter->hasAnnotation(ConsoleParameterOption::class) || $parameterType->isArrayButNotClassBasedCollection() || $parameterType->isBoolean();

                $parameters[]          = $interfaceParameter->hasDefaultValue()
                    ? ConsoleCommandParameter::createWithDefaultValue($interfaceParameter->getName(), $headerName, $isOption, $interfaceParameter->getTypeDescriptor()->isArrayButNotClassBasedCollection(), $interfaceParameter->getDefaultValue())
                    : ConsoleCommandParameter::create($interfaceParameter->getName(), $headerName, $isOption, $interfaceParameter->getTypeDescriptor()->isArrayButNotClassBasedCollection());
            }
        }

        return [$parameterConverters, $parameters];
    }

    public function prepare(Configuration $messagingConfiguration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        foreach ($this->oneTimeCommandHandlers as $oneTimeCommand) {
            $messagingConfiguration->registerMessageHandler($oneTimeCommand);
        }
        foreach ($this->oneTimeCommandConfigurations as $oneTimeCommandConfiguration) {
            $messagingConfiguration->registerConsoleCommand($oneTimeCommandConfiguration);
        }
    }

    public function canHandle($extensionObject): bool
    {
        return false;
    }

    public function getModulePackageName(): string
    {
        return ModulePackageList::CORE_PACKAGE;
    }
}
